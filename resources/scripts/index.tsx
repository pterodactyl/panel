import { createRoot } from 'react-dom/client';
import { App } from '@/components/App';

// Enable language support.
import './i18n';

createRoot(document.getElementById('app')!).render(<App />);
