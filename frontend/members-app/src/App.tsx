import { Routes, Route, Navigate } from 'react-router-dom';
import AppLayout from './layouts/AppLayout';
import MembersListPage from './pages/MembersListPage';
import MemberDetailPage from './pages/MemberDetailPage';
import MemberFormPage from './pages/MemberFormPage';

export default function App() {
  return (
    <Routes>
      <Route element={<AppLayout />}>
        <Route index element={<MembersListPage />} />
        <Route path="new" element={<MemberFormPage />} />
        <Route path=":id" element={<MemberDetailPage />} />
        <Route path=":id/edit" element={<MemberFormPage />} />
        <Route path="*" element={<Navigate to="." replace />} />
      </Route>
    </Routes>
  );
}