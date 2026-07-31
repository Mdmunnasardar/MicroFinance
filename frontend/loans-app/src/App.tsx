import { Routes, Route, Navigate } from 'react-router-dom';
import AppLayout from './layouts/AppLayout';
import LoansListPage from './pages/LoansListPage';
import LoanDetailPage from './pages/LoanDetailPage';
import LoanFormPage from './pages/LoanFormPage';
import PaymentPage from './pages/PaymentPage';

export default function App() {
  return (
    <Routes>
      <Route element={<AppLayout />}>
        <Route index element={<LoansListPage />} />
        <Route path="new" element={<LoanFormPage />} />
        <Route path=":id" element={<LoanDetailPage />} />
        <Route path=":id/edit" element={<LoanFormPage />} />
        <Route path=":id/payment" element={<PaymentPage />} />
        <Route path="*" element={<Navigate to="." replace />} />
      </Route>
    </Routes>
  );
}