# `collections/`

Unified entry point for **installments + due_system + loans/payment** — three related areas that all deal with money collection from members.

## Why this folder exists

The legacy codebase split collections-related pages across three folders:

| Old URL | Controller |
|---------|-----------|
| `installments/index.php` | InstallmentsListController |
| `installments/payment.php` | InstallmentPaymentController |
| `installments/payment_list.php` | InstallmentPaymentListController |
| `installments/edit.php` | InstallmentEditController |
| `installments/delete.php` | InstallmentDeleteController |
| `due_system/index.php` | DueListController |
| `due_system/overdue.php` | DueOverdueController |
| `due_system/report.php` | DueReportController |
| `loans/payment.php` | LoanPaymentController |

`collections/` gives a single URL prefix (`/MicroFinance/collections/`) that bundles them under one navigation entry. The legacy URLs still work for backward compatibility.

## Routing map

| New URL | Maps to |
|---------|---------|
| `collections/` | installments list (InstallmentsListController) |
| `collections/payment` | record a payment (InstallmentPaymentController) |
| `collections/payment-list` | list of payments made (InstallmentPaymentListController) |
| `collections/overdue` | overdue loans report (DueOverdueController) |
| `collections/due-list` | full dues list (DueListController) |
| `collections/report` | collections report (DueReportController) |
| `collections/edit?id=N` | edit installment (InstallmentEditController) |
| `collections/delete?id=N` | delete installment (InstallmentDeleteController) |

`loans/payment.php` is the alternative path for recording a payment; it points at LoanPaymentController. Future step: consolidate `InstallmentPaymentController` and `LoanPaymentController` into a single `Collections/RecordPaymentController` (they overlap).

## Companion React app

`../collections-app/` is the React UI for the same module.
