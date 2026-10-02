<?php
/**
 * Smart Library Management System - Books & Catalog Controller
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
    $books = read_json('books.json');
    $category = trim($_GET['category'] ?? '');
    $search = strtolower(trim($_GET['search'] ?? ''));

    $filtered = array_filter($books, function ($b) use ($category, $search) {
        // Category Filter
        if (!empty($category) && strcasecmp($category, 'All') !== 0) {
            if (strcasecmp($b['category'], $category) !== 0) {
                return false;
            }
        }
        // Search Filter (Title, Author, ISBN)
        if (!empty($search)) {
            $matchesTitle = strpos(strtolower($b['title']), $search) !== false;
            $matchesAuthor = strpos(strtolower($b['author']), $search) !== false;
            $matchesIsbn = strpos(strtolower($b['isbn']), $search) !== false;
            if (!$matchesTitle && !$matchesAuthor && !$matchesIsbn) {
                return false;
            }
        }
        return true;
    });

    json_response(true, 'Books retrieved successfully.', array_values($filtered));
}

// 2. Get Single Book Details
function get_single_book() {
    $id = (int)($_GET['id'] ?? 0);
    $books = read_json('books.json');

    foreach ($books as $b) {
        if ($b['id'] === $id) {
            json_response(true, 'Book details found.', $b);
        }
    }

    json_response(false, 'Book not found.', null, 404);
}

// 3. Add New Book (Admin Only)
function add_book() {
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $copies = (int)($_POST['copies'] ?? 1);
    $description = trim($_POST['description'] ?? '');
    $image = trim($_POST['image'] ?? 'b1.jpg');

    if (empty($title) || empty($author) || empty($isbn)) {
        json_response(false, 'Title, Author, and ISBN are required.', null, 400);
    }

    $books = read_json('books.json');
    $newId = count($books) > 0 ? max(array_column($books, 'id')) + 1 : 1;

    $newBook = [
        'id' => $newId,
        'title' => $title,
        'author' => $author,
        'category' => $category ?: 'General',
        'isbn' => $isbn,
        'total_copies' => $copies,
        'available_copies' => $copies,
        'image' => $image,
        'description' => $description ?: 'No description provided.'
    ];

    $books[] = $newBook;
    write_json('books.json', $books);

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

    $books = read_json('books.json');
    $updated = false;

    foreach ($books as &$b) {
        if ($b['id'] === $id) {
            if (!empty($title)) $b['title'] = $title;
            if (!empty($author)) $b['author'] = $author;
            if (!empty($category)) $b['category'] = $category;
            if (!empty($isbn)) $b['isbn'] = $isbn;
            if ($totalCopies !== null) {
                $difference = $totalCopies - $b['total_copies'];
                $b['total_copies'] = $totalCopies;
                $b['available_copies'] = max(0, $b['available_copies'] + $difference);
            }
            $updated = true;
            break;
        }
    }

    if ($updated) {
        write_json('books.json', $books);
        json_response(true, 'Book updated successfully.');
    } else {
        json_response(false, 'Book not found for update.', null, 404);
    }
}

// 5. Delete Book (Admin Only)
function delete_book() {
    $id = (int)($_POST['id'] ?? 0);
    $books = read_json('books.json');
    $initialCount = count($books);

    $books = array_filter($books, fn($b) => $b['id'] !== $id);

    if (count($books) < $initialCount) {
        write_json('books.json', array_values($books));
        json_response(true, 'Book removed from library catalog.');
    } else {
        json_response(false, 'Book not found to delete.', null, 404);
    }
}
?>
