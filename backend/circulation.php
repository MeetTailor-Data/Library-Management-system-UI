<?php
/**
 * Smart Library Management System - Circulation Controller
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

    if (empty($studentId)) {
        json_response(false, 'Student ID is required.', null, 400);
    }

    $books = read_json('books.json');
    $issuedList = read_json('issued_books.json');

    // 1. Find the target book
    $targetBookIndex = -1;
    foreach ($books as $index => $b) {
        if ($b['id'] === $bookId || strcasecmp($b['title'], $bookTitle) === 0) {
            $targetBookIndex = $index;
            break;
        }
    }

    if ($targetBookIndex === -1) {
        json_response(false, 'Requested book not found in library inventory.', null, 404);
    }

    $book = &$books[$targetBookIndex];

    // 2. Check stock availability
    if ($book['available_copies'] <= 0) {
        json_response(false, "Sorry, '{$book['title']}' is currently out of stock (0 available).", null, 400);
    }

    // 3. Enforce Borrow Limit (Max 3 active books per student)
    $activeBorrows = array_filter($issuedList, function ($item) use ($studentId) {
        return strcasecmp($item['user_id'], $studentId) === 0 && $item['status'] !== 'Returned';
    });

    if (count($activeBorrows) >= 3) {
        json_response(false, "Borrow limit exceeded: Student ({$studentId}) already has 3 active borrowed books. Please return a book first.", null, 400);
    }

    // 4. Prevent duplicate issuing of the exact same book
    foreach ($activeBorrows as $item) {
        if ($item['book_id'] === $book['id']) {
            json_response(false, "Duplicate checkout: Student ({$studentId}) already has an active copy of '{$book['title']}'.", null, 400);
        }
    }

    // 5. Decrement book stock
    $book['available_copies'] = max(0, $book['available_copies'] - 1);
    write_json('books.json', $books);

    // 6. Generate Issue Transaction with 14-day Due Date
    $issueDate = date('Y-m-d');
    $dueDate = date('Y-m-d', strtotime('+14 days'));

    $newId = count($issuedList) > 0 ? max(array_column($issuedList, 'id')) + 1 : 1;
    $newIssue = [
        'id' => $newId,
        'user_id' => $studentId,
        'student_name' => $_POST['student_name'] ?? ('Student ' . $studentId),
        'book_id' => $book['id'],
        'book_title' => $book['title'],
        'isbn' => $book['isbn'],
        'issue_date' => $issueDate,
        'due_date' => $dueDate,
        'return_date' => null,
        'status' => 'Issued',
        'fine' => 0
    ];

    $issuedList[] = $newIssue;
    write_json('issued_books.json', $issuedList);

    json_response(true, "Book '{$book['title']}' successfully issued to Student {$studentId}. Return due date: {$dueDate}.", $newIssue);
}

// 2. Return Book
function return_book() {
    $issueId = (int)($_POST['issue_id'] ?? 0);
    $issuedList = read_json('issued_books.json');
    $books = read_json('books.json');

    $targetIssueIndex = -1;
    foreach ($issuedList as $index => $item) {
        if ($item['id'] === $issueId && $item['status'] !== 'Returned') {
            $targetIssueIndex = $index;
            break;
        }
    }

    if ($targetIssueIndex === -1) {
        json_response(false, 'Active issue record not found.', null, 404);
    }

    $issue = &$issuedList[$targetIssueIndex];
    $returnDate = date('Y-m-d');

    // 1. Calculate Overdue Fine
    $fineInfo = calculate_overdue_fine($issue['due_date'], $returnDate);
    $issue['return_date'] = $returnDate;
    $issue['status'] = 'Returned';
    $issue['fine'] = $fineInfo['fine_amount'];

    // 2. Restore available book stock
    foreach ($books as &$b) {
        if ($b['id'] === $issue['book_id']) {
            $b['available_copies'] = min($b['total_copies'], $b['available_copies'] + 1);
            break;
        }
    }

    write_json('books.json', $books);
    write_json('issued_books.json', $issuedList);

    $msg = $fineInfo['is_overdue'] 
        ? "Book returned with overdue fine of ₹{$fineInfo['fine_amount']} for {$fineInfo['overdue_days']} late days."
        : "Book returned on time! Stock count restored.";

    json_response(true, $msg, [
        'fine' => $fineInfo['fine_amount'],
        'is_overdue' => $fineInfo['is_overdue']
    ]);
}

// 3. Get Student Active & Historical Borrows
function get_student_borrows() {
    $studentId = strtoupper(trim($_GET['student_id'] ?? ''));

    if (empty($studentId)) {
        json_response(false, 'Student ID is required.', null, 400);
    }

    $issuedList = read_json('issued_books.json');
    $studentBorrows = [];

    foreach ($issuedList as $item) {
        if (strcasecmp($item['user_id'], $studentId) === 0) {
            // Live compute overdue status and fine if active
            if ($item['status'] !== 'Returned') {
                $fineInfo = calculate_overdue_fine($item['due_date']);
                if ($fineInfo['is_overdue']) {
                    $item['status'] = 'Overdue';
                    $item['fine'] = $fineInfo['fine_amount'];
                }
            }
            $studentBorrows[] = $item;
        }
    }

    json_response(true, 'Student borrow history loaded.', $studentBorrows);
}

// 4. List All Circulation for Admin Panel
function list_all_circulation() {
    $issuedList = read_json('issued_books.json');

    // Live update overdue records
    foreach ($issuedList as &$item) {
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
