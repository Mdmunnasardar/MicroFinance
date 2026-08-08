import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { AuthProvider } from './context/AuthContext.jsx';
import InstallmentsStandaloneApp from './InstallmentsStandaloneApp.jsx';
import './assets/css/common.css';
import './assets/css/layout.css';
import './assets/css/installments.css';
import './pages/installments/tabs.css';

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <BrowserRouter basename="/MicroFinance/installments">
      <AuthProvider>
        <InstallmentsStandaloneApp />
      </AuthProvider>
    </BrowserRouter>
  </React.StrictMode>,
);
