// Every engine view definition: the source of the SDK's views.json and of the workbench.
import './account';
import './activity';
import './auth';
import './dashboard';
import './layouts';
import './pages';
import './server/backups';
import './server/conflict';
import './server/console';
import './server/databases';
import './server/features';
import './server/files';
import './server/network';
import './server/schedules';
import './server/settings';
import './server/startup';
import './server/users';

export { getViewDefinitions } from './registry';
