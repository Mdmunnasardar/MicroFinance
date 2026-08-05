<?php

declare(strict_types=1);

return [
    // Auth
    ['POST',   '/api/auth/login',             \App\Controllers\JsonApi\AuthController::class,            'login',     []],
    ['POST',   '/api/auth/logout',            \App\Controllers\JsonApi\AuthController::class,            'logout',    ['auth']],
    ['GET',    '/api/auth/session',           \App\Controllers\JsonApi\AuthController::class,            'session',   []],

    // Dashboard
    ['GET',    '/api/dashboard-stats',        \App\Controllers\JsonApi\DashboardController::class,       'stats',     ['auth']],

    // Profile
    ['GET',    '/api/profile',                \App\Controllers\JsonApi\ProfileController::class,        'show',          ['auth']],
    ['PUT',    '/api/profile',                \App\Controllers\JsonApi\ProfileController::class,        'update',        ['auth']],
    ['POST',   '/api/profile/avatar',         \App\Controllers\JsonApi\ProfileController::class,        'uploadAvatar',  ['auth']],
    ['PUT',    '/api/profile/password',       \App\Controllers\JsonApi\ProfileController::class,        'changePassword',['auth']],

    // Search + notifications
    ['GET',    '/api/search',                 \App\Controllers\JsonApi\SearchController::class,         'index',     ['auth']],
    ['GET',    '/api/notifications',          \App\Controllers\JsonApi\NotificationsController::class,  'index',     ['auth']],
    ['POST',   '/api/notifications/{id}/read',\App\Controllers\JsonApi\NotificationsController::class,  'markRead',  ['auth']],

    // Members
    ['GET',    '/api/members',                                       \App\Controllers\JsonApi\MembersController::class, 'index',         ['auth']],
    ['POST',   '/api/members',                                       \App\Controllers\JsonApi\MembersController::class, 'store',         ['auth']],
    // Static "search" route must come BEFORE the dynamic "{id}" route,
    // otherwise /api/members/search is captured by the {id} placeholder.
    ['GET',    '/api/members/search',                                \App\Controllers\JsonApi\MembersController::class, 'search',        ['auth']],
    ['GET',    '/api/members/{id}',                                  \App\Controllers\JsonApi\MembersController::class, 'show',          ['auth']],
    ['PUT',    '/api/members/{id}',                                  \App\Controllers\JsonApi\MembersController::class, 'update',        ['auth']],
    ['DELETE', '/api/members/{id}',                                  \App\Controllers\JsonApi\MembersController::class, 'destroy',       ['auth']],
    ['GET',    '/api/members/{id}/transactions',                     \App\Controllers\JsonApi\MembersController::class, 'transactions',  ['auth']],
    ['GET',    '/api/members/{id}/loans',                            \App\Controllers\JsonApi\MembersController::class, 'loans',         ['auth']],
    ['GET',    '/api/members/{id}/savings',                          \App\Controllers\JsonApi\MembersController::class, 'savings',       ['auth']],

    // Committees
    ['GET',    '/api/committees',                                    \App\Controllers\JsonApi\CommitteesController::class, 'index',         ['auth']],
    ['POST',   '/api/committees',                                    \App\Controllers\JsonApi\CommitteesController::class, 'store',         ['auth']],
    ['GET',    '/api/committees/{id}',                               \App\Controllers\JsonApi\CommitteesController::class, 'show',          ['auth']],
    ['PUT',    '/api/committees/{id}',                               \App\Controllers\JsonApi\CommitteesController::class, 'update',        ['auth']],
    ['DELETE', '/api/committees/{id}',                               \App\Controllers\JsonApi\CommitteesController::class, 'destroy',       ['auth']],
    ['GET',    '/api/committees/{id}/members',                       \App\Controllers\JsonApi\CommitteesController::class, 'members',       ['auth']],
    ['POST',   '/api/committees/{id}/members',                       \App\Controllers\JsonApi\CommitteesController::class, 'addMember',     ['auth']],
    ['DELETE', '/api/committees/{id}/members/{memberId}',            \App\Controllers\JsonApi\CommitteesController::class, 'removeMember',  ['auth']],

    // Loans
    ['GET',    '/api/loans',                                         \App\Controllers\JsonApi\LoansController::class, 'index',         ['auth']],
    ['POST',   '/api/loans',                                         \App\Controllers\JsonApi\LoansController::class, 'store',         ['auth']],
    ['GET',    '/api/loans/{id}',                                    \App\Controllers\JsonApi\LoansController::class, 'show',          ['auth']],
    ['PUT',    '/api/loans/{id}',                                    \App\Controllers\JsonApi\LoansController::class, 'update',        ['auth']],
    ['DELETE', '/api/loans/{id}',                                    \App\Controllers\JsonApi\LoansController::class, 'destroy',       ['auth']],
    ['POST',   '/api/loans/{id}/status',                             \App\Controllers\JsonApi\LoansController::class, 'updateStatus',  ['auth']],

    // Installments
    ['GET',    '/api/installments',                                  \App\Controllers\JsonApi\InstallmentsController::class, 'index',   ['auth']],
    ['POST',   '/api/installments',                                  \App\Controllers\JsonApi\InstallmentsController::class, 'store',   ['auth']],
    ['GET',    '/api/installments/{id}',                             \App\Controllers\JsonApi\InstallmentsController::class, 'show',    ['auth']],
    ['PUT',    '/api/installments/{id}',                             \App\Controllers\JsonApi\InstallmentsController::class, 'update',  ['auth']],
    ['DELETE', '/api/installments/{id}',                             \App\Controllers\JsonApi\InstallmentsController::class, 'destroy', ['auth']],

    // Savings
    ['GET',    '/api/savings',                                       \App\Controllers\JsonApi\SavingsController::class, 'index',         ['auth']],
    ['POST',   '/api/savings',                                       \App\Controllers\JsonApi\SavingsController::class, 'store',         ['auth']],
    ['GET',    '/api/savings/{id}',                                  \App\Controllers\JsonApi\SavingsController::class, 'show',          ['auth']],
    ['PUT',    '/api/savings/{id}',                                  \App\Controllers\JsonApi\SavingsController::class, 'update',        ['auth']],
    ['DELETE', '/api/savings/{id}',                                  \App\Controllers\JsonApi\SavingsController::class, 'destroy',       ['auth']],
    ['GET',    '/api/savings/member/{memberId}',                     \App\Controllers\JsonApi\SavingsController::class, 'byMember',      ['auth']],
    ['POST',   '/api/savings/deposits',                              \App\Controllers\JsonApi\SavingsController::class, 'deposit',       ['auth']],
    ['POST',   '/api/savings/withdrawals',                           \App\Controllers\JsonApi\SavingsController::class, 'withdraw',      ['auth']],
    ['GET',    '/api/savings/transactions',                          \App\Controllers\JsonApi\SavingsController::class, 'transactions',  ['auth']],

    // Due system
    ['GET',    '/api/due-system',                                    \App\Controllers\JsonApi\DueSystemController::class, 'index',     ['auth']],
    ['GET',    '/api/due-system/{id}',                               \App\Controllers\JsonApi\DueSystemController::class, 'show',      ['auth']],
    ['POST',   '/api/due-system/{id}/collect',                       \App\Controllers\JsonApi\DueSystemController::class, 'collect',   ['auth']],
];