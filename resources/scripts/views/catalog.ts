// Every engine view definition: the source of the SDK's views.json and of the workbench.
import '@/components/server/files/FileDetailsView';
import '@/components/server/files/FileEditorView';
import '@/components/server/files/FileManagerView';
import './account';
import './activity';
import './auth';
import './dashboard';
import './layouts';
import './pages';
import './server/backups';
import './server/conflict';
import './server/schedules';
import './server/settings';
import './server/users';

export { getViewDefinitions } from './registry';
