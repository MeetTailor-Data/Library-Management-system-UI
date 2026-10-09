<?php
/**
 * Smart Library Management System - Circulation Controller (SQL PDO)
 * Handles Book Issue, Return, Stock Sync, Limits, and Automated Fine Calculations.
 */

require_once __DIR__ . '/db.php';

$action = $_REQUEST['action'] ?? 'list';

switch ($action) {
    case 'issue':
        issue_book();
        break;

    case 'return':
        return_book();
        break;

    case 'student_borrows':
        get_student_borrows();
        break;

    case 'list_all':
        list_all_circulation();
        break;

    default:
        json_response(false, 'Invalid circulation action specified.', null, 400);
}

// 1. Issue Book to Student
function issue_book() {
    $studentId = strtoupper(trim($_POST['student_id'] ?? ''));
    $bookId = (int)($_POST['book_id'] ?? 0);
    $bookTitle = trim($_POST['book_title'] ?? '');
    $studentName = trim($_POST['student_name'] ?? ('Student ' . $studentId));

    if (empty($studentId)) {
        json_response(false, 'Student ID is required.', null, 400);
    }

    $db = get_db();

    // 1. Find the target book
    if ($bookId > 0) {
        $stmt = $db->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
        $stmt->execute([$bookId]);
    } else {
        $stmt = $db->prepare("SELECT * FROM books WHERE LOWER(title) = LOWER(?) LIMIT 1");
        $stmt->execute([$bookTitle]);
    }
    $book = $stmt->fetch();

    if (!$book) {
        json_response(false, 'Requested book not found in library inventory.', null, 404);
    }

    // 2. Check stock availability
    if ((int)$book['available_copies'] <= 0) {
        json_response(false, "Sorry, '{$book['title']}' is currently out of stock (0 available).", null, 400);
    }

    // 3. Enforce Borrow Limit (Max 3 active books per student)
    $countStmt = $db->prepare("SELECT COUNT(*) FROM issued_books WHERE LOWER(user_id) = LOWER(?) AND status != 'Returned'");
    $countStmt->execute([$studentId]);
    $activeCount = (int)$countStmt->fetchColumn();

    if ($activeCount >= 3) {
        json_response(false, "Borrow limit exceeded: Student ({$studentId}) already has 3 active borrowed books. Please return a book first.", null, 400);
    }

    // 4. Prevent duplicate issuing of the exact same book
    $dupStmt = $db->prepare("SELECT id FROM issued_books WHERE LOWER(user_id) = LOWER(?) AND book_id = ? AND status != 'Returned' LIMIT 1");
    $dupStmt->execute([$studentId, $book['id']]);
    if ($dupStmt->fetch()) {
        json_response(false, "Duplicate checkout: Student ({$studentId}) already has an active copy of '{$book['title']}'.", null, 400);
    }

    // 5. Execute transaction: Decrement book stock & create issue record
    try {
        $db->beginTransaction();

        $updateStockStmt = $db->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE id = ? AND available_copies > 0");
        $updateStockStmt->execute([$book['id']]);

        if ($updateStockStmt->rowCount() === 0) {
            $db->rollBack();
            json_response(false, "Book was just checked out by another user. Out of stock.", null, 400);
        }

        $issueDate = date('Y-m-d');
        $dueDate = date('Y-m-d', strtotime('+14 days'));

        $insertIssueStmt = $db->prepare("INSERT INTO issued_books (user_id, student_name, book_id, book_title, isbn, issue_date, due_date, return_date, status, fine) VALUES (?, ?, ?, ?, ?, ?, ?, NULL, 'Issued', 0.00)");
        $insertIssueStmt->execute([$studentId, $studentName, $book['id'], $book['title'], $book['isbn'], $issueDate, $dueDate]);
        $newId = (int)$db->lastInsertId();

        $db->commit();

        $newIssue = [
            'id' => $newId,
            'user_id' => $studentId,
            'student_name' => $studentName,
            'book_id' => (int)$book['id'],
            'book_title' => $book['title'],
            'isbn' => $book['isbn'],
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'return_date' => null,
            'status' => 'Issued',
            'fine' => 0
        ];

        json_response(true, "Book '{$book['title']}' successfully issued to Student {$studentId}. Return due date: {$dueDate}.", $newIssue);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        json_response(false, 'Transaction failed: ' . $e->getMessage(), null, 500);
    }
}

// 2. Return Book
function return_book() {
    $issueId = (int)($_POST['issue_id'] ?? 0);
    $db = get_db();

    $stmt = $db->prepare("SELECT * FROM issued_books WHERE id = ? AND status != 'Returned' LIMIT 1");
    $stmt->execute([$issueId]);
    $issue = $stmt->fetch();

    if (!$issue) {
        json_response(false, 'Active issue record not found.', null, 404);
    }

    $returnDate = date('Y-m-d');
    $fineInfo = calculate_overdue_fine($issue['due_date'], $returnDate);
    $fineAmount = $fineInfo['fine_amount'];

    try {
        $db->beginTransaction();

        // 1. Mark issue as returned
        $updateIssueStmt = $db->prepare("UPDATE issued_books SET return_date = ?, status = 'Returned', fine = ? WHERE id = ?");
        $updateIssueStmt->execute([$returnDate, $fineAmount, $issueId]);

        // 2. Restore available copy count in books table
        $updateBookStmt = $db->prepare("UPDATE books SET available_copies = LEAST(total_copies, available_copies + 1) WHERE id = ?");
        $updateBookStmt->execute([$issue['book_id']]);

        $db->commit();

        $msg = $fineInfo['is_overdue'] 
            ? "Book returned with overdue fine of ₹{$fineAmount} for {$fineInfo['overdue_days']} late days."
            : "Book returned on time! Stock count restored.";

        json_response(true, $msg, [
            'fine' => $fineAmount,
            'is_overdue' => $fineInfo['is_overdue']
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        json_response(false, 'Failed to process return: ' . $e->getMessage(), null, 500);
    }
}

// 3. Get Student Active & Historical Borrows
function get_student_borrows() {
    $studentId = strtoupper(trim($_GET['student_id'] ?? ''));

    if (empty($studentId)) {
        json_response(false, 'Student ID is required.', null, 400);
    }

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM issued_books WHERE LOWER(user_id) = LOWER(?) ORDER BY id DESC");
    $stmt->execute([$studentId]);
    $studentBorrows = $stmt->fetchAll();

    foreach ($studentBorrows as &$item) {
        $item['id'] = (int)$item['id'];
        $item['book_id'] = (int)$item['book_id'];
        $item['fine'] = (float)$item['fine'];

        if ($item['status'] !== 'Returned') {
            $fineInfo = calculate_overdue_fine($item['due_date']);
            if ($fineInfo['is_overdue']) {
                $item['status'] = 'Overdue';
                $item['fine'] = $fineInfo['fine_amount'];
            }
        }
    }

    json_response(true, 'Student borrow history loaded.', $studentBorrows);
}

// 4. List All Circulation for Admin Panel
function list_all_circulation() {
    $db = get_db();
    $stmt = $db->query("SELECT * FROM issued_books ORDER BY id DESC");
    $issuedList = $stmt->fetchAll();

    foreach ($issuedList as &$item) {
        $item['id'] = (int)$item['id'];
        $item['book_id'] = (int)$item['book_id'];
        $item['fine'] = (float)$item['fine'];

        if ($item['status'] !== 'Returned') {
            $fineInfo = calculate_overdue_fine($item['due_date']);
            if ($fineInfo['is_overdue']) {
                $item['status'] = 'Overdue';
                $item['fine'] = $fineInfo['fine_amount'];
            }
        }
    }

    json_response(true, 'All circulation records loaded.', $issuedList);
}
?>
