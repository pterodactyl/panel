import { render } from 'react-dom';
import { App } from '@/components/App';

// Enable language support.
import './i18n';

render(<App />, document.getElementById('app'));
