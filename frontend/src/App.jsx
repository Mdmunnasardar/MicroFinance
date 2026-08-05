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
import NotFoundPage from './pages/NotFoundPage';

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
      </Route>

      <Route path="*" element={<NotFoundPage />} />
    </Routes>
  );
}