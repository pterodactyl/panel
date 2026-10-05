import dayjs from 'dayjs';
import advancedFormat from 'dayjs/plugin/advancedFormat';
import relativeTime from 'dayjs/plugin/relativeTime';

// Import dayjs from this module so these plugins are registered.
dayjs.extend(advancedFormat);
dayjs.extend(relativeTime);

export default dayjs;
