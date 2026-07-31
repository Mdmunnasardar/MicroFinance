import { Routes, Route, Navigate } from 'react-router-dom';
import AppLayout from './layouts/AppLayout';
import SavingsListPage from './pages/SavingsListPage';
import SavingsDetailPage from './pages/SavingsDetailPage';
import DepositPage from './pages/DepositPage';
import WithdrawPage from './pages/WithdrawPage';

export default function App() {
  return (
    <Routes>
      <Route element={<AppLayout />}>
        <Route index element={<SavingsListPage />} />
        <Route path=":memberId" element={<SavingsDetailPage />} />
        <Route path=":memberId/deposit" element={<DepositPage />} />
        <Route path=":memberId/withdraw" element={<WithdrawPage />} />
        <Route path="*" element={<Navigate to="." replace />} />
      </Route>
    </Routes>
  );
}