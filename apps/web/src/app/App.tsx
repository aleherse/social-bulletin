import { BrowserRouter, Route, Routes } from 'react-router-dom';
import { HomePage } from '../pages/home/ui/HomePage';
import { TermsPage } from '../pages/terms/ui/TermsPage';

export function App() {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/terms" element={<TermsPage />} />
      </Routes>
    </BrowserRouter>
  );
}
