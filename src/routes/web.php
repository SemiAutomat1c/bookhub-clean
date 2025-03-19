// Define web routes
return [
    // Public routes
    '' => [
        'path' => 'views/index.html',
        'auth' => false
    ],
    'index' => [
        'path' => 'views/index.html',
        'auth' => false
    ],
    'search' => [
        'path' => 'views/search.html',
        'auth' => false
    ],
    'sign-in' => [
        'path' => 'views/sign-in.html',
        'auth' => false
    ],
    
    // Protected routes (require authentication)
    'reader' => [
        'path' => 'views/reader.html',
        'auth' => true
    ],
    'profile' => [
        'path' => 'views/profile.html',
        'auth' => true
    ],
    'reading-list' => [
        'path' => 'views/reading-list.html',
        'auth' => true
    ],
    
    // Admin routes
    'admin' => [
        'path' => 'views/admin.html',
        'auth' => true,
        'admin' => true
    ]
]; 