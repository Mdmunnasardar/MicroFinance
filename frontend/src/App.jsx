import { Routes, Route, Navigate } from 'react-router-dom';
import AppLayout from './components/layout/AppLayout';
import ProtectedRoute from './components/ProtectedRoute';
import LoginPage from './pages/LoginPage';
import DashboardPage from './pages/DashboardPage';
import InstallmentsPage from './pages/InstallmentsPage';
import MembersPage from './pages/MembersPage';
import MemberFormPage from './pages/MemberFormPage';
import MemberProfilePage from './pages/MemberProfilePage';
import CommitteesPage from './pages/CommitteesPage';
import CommitteeFormPage from './pages/CommitteeFormPage';
import CommitteeViewPage from './pages/CommitteeViewPage';
import CommitteeMembersPage from './pages/CommitteeMembersPage';
import LoansPage from './pages/LoansPage';
import LoanFormPage from './pages/LoanFormPage';
import LoanViewPage from './pages/LoanViewPage';
import LoanPaymentPage from './pages/LoanPaymentPage';
import NotFoundPage from './pages/NotFoundPage';
import SavingsPage from './pages/SavingsPage';
import SavingsFormPage from './pages/SavingsFormPage';
import SavingsTransactionPage from './pages/SavingsTransactionPage';
import SavingsTransactionsPage from './pages/SavingsTransactionsPage';
import DueSystemPage from './pages/DueSystemPage';
import DueSystemOverduePage from './pages/DueSystemOverduePage';
import DueSystemReportPage from './pages/DueSystemReportPage';

export default function App() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />

      <Route element={<ProtectedRoute><AppLayout /></ProtectedRoute>}>
        <Route path="/" element={<DashboardPage />} />
        <Route path="/installments" element={<InstallmentsPage />} />
        <Route path="/members" element={<MembersPage />} />
        <Route path="/members/new" element={<MemberFormPage mode="create" />} />
        <Route path="/members/:id/edit" element={<MemberFormPage mode="edit" />} />
        <Route path="/members/:id" element={<MemberProfilePage />} />
        <Route path="/committees" element={<CommitteesPage />} />
        <Route path="/committees/new" element={<CommitteeFormPage mode="create" />} />
        <Route path="/committees/:id/edit" element={<CommitteeFormPage mode="edit" />} />
        <Route path="/committees/:id/members" element={<CommitteeMembersPage />} />
        <Route path="/committees/:id" element={<CommitteeViewPage />} />
        <Route path="/loans" element={<LoansPage />} />
        <Route path="/loans/new" element={<LoanFormPage mode="create" />} />
        <Route path="/loans/:id/edit" element={<LoanFormPage mode="edit" />} />
        <Route path="/loans/:id/payment" element={<LoanPaymentPage />} />
        <Route path="/loans/:id" element={<LoanViewPage />} />
        <Route path="/savings" element={<SavingsPage />} />
        <Route path="/savings/new" element={<SavingsFormPage />} />
        <Route path="/savings/deposit" element={<SavingsTransactionPage mode="deposit" />} />
        <Route path="/savings/withdraw" element={<SavingsTransactionPage mode="withdraw" />} />
        <Route path="/savings/transactions" element={<SavingsTransactionsPage />} />
        <Route path="/due-system" element={<DueSystemPage />} />
        <Route path="/due-system/overdue" element={<DueSystemOverduePage />} />
        <Route path="/due-system/report" element={<DueSystemReportPage />} />
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}