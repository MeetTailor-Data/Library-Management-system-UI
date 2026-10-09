<?php
/**
 * Smart Library Management System - Books & Catalog Controller (SQL PDO)
 * Handles book listing, searching, filtering, adding, editing, and deleting.
 */

require_once __DIR__ . '/db.php';

$action = $_REQUEST['action'] ?? 'list';

switch ($action) {
    case 'list':
        list_books();
        break;

    case 'get':
        get_single_book();
        break;

    case 'add':
        add_book();
        break;

    case 'edit':
        edit_book();
        break;

    case 'delete':
        delete_book();
        break;

    default:
        json_response(false, 'Invalid book action specified.', null, 400);
}

// 1. List Books (with optional Category and Search filters)
function list_books() {
    $db = get_db();
    $category = trim($_GET['category'] ?? '');
    $search = trim($_GET['search'] ?? '');

    $sql = "SELECT id, title, author, category, isbn, total_copies, available_copies, image, description FROM books WHERE 1=1";
    $params = [];

    // Category Filter
    if (!empty($category) && strcasecmp($category, 'All') !== 0) {
        $sql .= " AND LOWER(category) = LOWER(?)";
        $params[] = $category;
    }

    // Search Filter (Title, Author, ISBN)
    if (!empty($search)) {
        $sql .= " AND (LOWER(title) LIKE ? OR LOWER(author) LIKE ? OR LOWER(isbn) LIKE ?)";
        $searchTerm = '%' . strtolower($search) . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sql .= " ORDER BY id ASC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();

    // Format types for consistency
    foreach ($books as &$b) {
        $b['id'] = (int)$b['id'];
        $b['total_copies'] = (int)$b['total_copies'];
        $b['available_copies'] = (int)$b['available_copies'];
    }

    json_response(true, 'Books retrieved successfully.', $books);
}

// 2. Get Single Book Details
function get_single_book() {
    $id = (int)($_GET['id'] ?? 0);
    $db = get_db();

    $stmt = $db->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $book = $stmt->fetch();

    if ($book) {
        $book['id'] = (int)$book['id'];
        $book['total_copies'] = (int)$book['total_copies'];
        $book['available_copies'] = (int)$book['available_copies'];
        json_response(true, 'Book details found.', $book);
    }

    json_response(false, 'Book not found.', null, 404);
}

// 3. Add New Book (Admin Only)
function add_book() {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? 'General');
    $isbn = trim($_POST['isbn'] ?? '');
    $copies = (int)($_POST['copies'] ?? 1);
    $description = trim($_POST['description'] ?? 'No description provided.');
    $image = trim($_POST['image'] ?? 'b1.jpg');

    if (empty($title) || empty($author) || empty($isbn)) {
        json_response(false, 'Title, Author, and ISBN are required.', null, 400);
    }

    $db = get_db();

    // Check ISBN uniqueness
    $checkStmt = $db->prepare("SELECT id FROM books WHERE isbn = ? LIMIT 1");
    $checkStmt->execute([$isbn]);
    if ($checkStmt->fetch()) {
        json_response(false, "A book with ISBN '{$isbn}' already exists.", null, 409);
    }

    $stmt = $db->prepare("INSERT INTO books (title, author, category, isbn, total_copies, available_copies, image, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$title, $author, $category, $isbn, $copies, $copies, $image, $description]);
    $newId = (int)$db->lastInsertId();

    $newBook = [
        'id' => $newId,
        'title' => $title,
        'author' => $author,
        'category' => $category,
        'isbn' => $isbn,
        'total_copies' => $copies,
        'available_copies' => $copies,
        'image' => $image,
        'description' => $description
    ];

    json_response(true, "Book '{$title}' added successfully to inventory.", $newBook);
}

// 4. Edit Book (Admin Only)
function edit_book() {
    $id = (int)($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $totalCopies = isset($_POST['total_copies']) ? (int)$_POST['total_copies'] : null;

    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM books WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $book = $stmt->fetch();

    if (!$book) {
        json_response(false, 'Book not found for update.', null, 404);
    }

    $newTitle = !empty($title) ? $title : $book['title'];
    $newAuthor = !empty($author) ? $author : $book['author'];
    $newCategory = !empty($category) ? $category : $book['category'];
    $newIsbn = !empty($isbn) ? $isbn : $book['isbn'];

    $newTotal = $book['total_copies'];
    $newAvailable = $book['available_copies'];

    if ($totalCopies !== null && $totalCopies >= 0) {
        $diff = $totalCopies - (int)$book['total_copies'];
        $newTotal = $totalCopies;
        $newAvailable = max(0, (int)$book['available_copies'] + $diff);
    }

    $updateStmt = $db->prepare("UPDATE books SET title = ?, author = ?, category = ?, isbn = ?, total_copies = ?, available_copies = ? WHERE id = ?");
    $updateStmt->execute([$newTitle, $newAuthor, $newCategory, $newIsbn, $newTotal, $newAvailable, $id]);

    json_response(true, 'Book updated successfully.');
}

// 5. Delete Book (Admin Only)
function delete_book() {
    $id = (int)($_POST['id'] ?? 0);
    $db = get_db();

    $stmt = $db->prepare("DELETE FROM books WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        json_response(true, 'Book removed from library catalog.');
    } else {
        json_response(false, 'Book not found to delete.', null, 404);
    }
}
?>
