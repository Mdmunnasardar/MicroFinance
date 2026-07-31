<?php

declare(strict_types=1);

return [
    // Auth
    ['POST',   '/api/auth/login',             \App\Controllers\Api\AuthController::class,            'login',     []],
    ['POST',   '/api/auth/logout',            \App\Controllers\Api\AuthController::class,            'logout',    ['auth']],
    ['GET',    '/api/auth/session',           \App\Controllers\Api\AuthController::class,            'session',   []],

    // Dashboard
    ['GET',    '/api/dashboard-stats',        \App\Controllers\Api\DashboardController::class,       'stats',     ['auth']],

    // Profile
    ['GET',    '/api/profile',                \App\Controllers\Api\ProfileController::class,        'show',          ['auth']],
    ['PUT',    '/api/profile',                \App\Controllers\Api\ProfileController::class,        'update',        ['auth']],
    ['POST',   '/api/profile/avatar',         \App\Controllers\Api\ProfileController::class,        'uploadAvatar',  ['auth']],
    ['PUT',    '/api/profile/password',       \App\Controllers\Api\ProfileController::class,        'changePassword',['auth']],

    // Search + notifications
    ['GET',    '/api/search',                 \App\Controllers\Api\SearchController::class,         'index',     ['auth']],
    ['GET',    '/api/notifications',          \App\Controllers\Api\NotificationsController::class,  'index',     ['auth']],
    ['POST',   '/api/notifications/{id}/read',\App\Controllers\Api\NotificationsController::class,  'markRead',  ['auth']],

    // Members
    ['GET',    '/api/members',                                       \App\Controllers\Api\MembersController::class, 'index',         ['auth']],
    ['POST',   '/api/members',                                       \App\Controllers\Api\MembersController::class, 'store',         ['auth']],
    ['GET',    '/api/members/{id}',                                  \App\Controllers\Api\MembersController::class, 'show',          ['auth']],
    ['PUT',    '/api/members/{id}',                                  \App\Controllers\Api\MembersController::class, 'update',        ['auth']],
    ['DELETE', '/api/members/{id}',                                  \App\Controllers\Api\MembersController::class, 'destroy',       ['auth']],
    ['GET',    '/api/members/{id}/transactions',                     \App\Controllers\Api\MembersController::class, 'transactions',  ['auth']],
    ['GET',    '/api/members/{id}/loans',                            \App\Controllers\Api\MembersController::class, 'loans',         ['auth']],
    ['GET',    '/api/members/{id}/savings',                          \App\Controllers\Api\MembersController::class, 'savings',       ['auth']],

    // Committees
    ['GET',    '/api/committees',                                    \App\Controllers\Api\CommitteesController::class, 'index',         ['auth']],
    ['POST',   '/api/committees',                                    \App\Controllers\Api\CommitteesController::class, 'store',         ['auth']],
    ['GET',    '/api/committees/{id}',                               \App\Controllers\Api\CommitteesController::class, 'show',          ['auth']],
    ['PUT',    '/api/committees/{id}',                               \App\Controllers\Api\CommitteesController::class, 'update',        ['auth']],
    ['DELETE', '/api/committees/{id}',                               \App\Controllers\Api\CommitteesController::class, 'destroy',       ['auth']],
    ['GET',    '/api/committees/{id}/members',                       \App\Controllers\Api\CommitteesController::class, 'members',       ['auth']],
    ['POST',   '/api/committees/{id}/members',                       \App\Controllers\Api\CommitteesController::class, 'addMember',     ['auth']],
    ['DELETE', '/api/committees/{id}/members/{memberId}',            \App\Controllers\Api\CommitteesController::class, 'removeMember',  ['auth']],

    // Loans
    ['GET',    '/api/loans',                                         \App\Controllers\Api\LoansController::class, 'index',         ['auth']],
    ['POST',   '/api/loans',                                         \App\Controllers\Api\LoansController::class, 'store',         ['auth']],
    ['GET',    '/api/loans/{id}',                                    \App\Controllers\Api\LoansController::class, 'show',          ['auth']],
    ['PUT',    '/api/loans/{id}',                                    \App\Controllers\Api\LoansController::class, 'update',        ['auth']],
    ['DELETE', '/api/loans/{id}',                                    \App\Controllers\Api\LoansController::class, 'destroy',       ['auth']],
    ['POST',   '/api/loans/{id}/status',                             \App\Controllers\Api\LoansController::class, 'updateStatus',  ['auth']],

    // Installments
    ['GET',    '/api/installments',                                  \App\Controllers\Api\InstallmentsController::class, 'index',   ['auth']],
    ['POST',   '/api/installments',                                  \App\Controllers\Api\InstallmentsController::class, 'store',   ['auth']],
    ['GET',    '/api/installments/{id}',                             \App\Controllers\Api\InstallmentsController::class, 'show',    ['auth']],
    ['PUT',    '/api/installments/{id}',                             \App\Controllers\Api\InstallmentsController::class, 'update',  ['auth']],
    ['DELETE', '/api/installments/{id}',                             \App\Controllers\Api\InstallmentsController::class, 'destroy', ['auth']],

    // Savings
    ['GET',    '/api/savings',                                       \App\Controllers\Api\SavingsController::class, 'index',         ['auth']],
    ['POST',   '/api/savings',                                       \App\Controllers\Api\SavingsController::class, 'store',         ['auth']],
    ['GET',    '/api/savings/{id}',                                  \App\Controllers\Api\SavingsController::class, 'show',          ['auth']],
    ['PUT',    '/api/savings/{id}',                                  \App\Controllers\Api\SavingsController::class, 'update',        ['auth']],
    ['DELETE', '/api/savings/{id}',                                  \App\Controllers\Api\SavingsController::class, 'destroy',       ['auth']],
    ['GET',    '/api/savings/member/{memberId}',                     \App\Controllers\Api\SavingsController::class, 'byMember',      ['auth']],
    ['POST',   '/api/savings/deposits',                              \App\Controllers\Api\SavingsController::class, 'deposit',       ['auth']],
    ['POST',   '/api/savings/withdrawals',                           \App\Controllers\Api\SavingsController::class, 'withdraw',      ['auth']],
    ['GET',    '/api/savings/transactions',                          \App\Controllers\Api\SavingsController::class, 'transactions',  ['auth']],

    // Due system
    ['GET',    '/api/due-system',                                    \App\Controllers\Api\DueSystemController::class, 'index',     ['auth']],
    ['GET',    '/api/due-system/{id}',                               \App\Controllers\Api\DueSystemController::class, 'show',      ['auth']],
    ['POST',   '/api/due-system/{id}/collect',                       \App\Controllers\Api\DueSystemController::class, 'collect',   ['auth']],
];