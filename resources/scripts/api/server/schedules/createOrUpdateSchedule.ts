import { rawDataToServerSchedule, Schedule } from '@/api/server/schedules/getServerSchedules';
import http from '@/api/http';

type Data = Pick<Schedule, 'cron' | 'name' | 'isActive'> & { id?: number }

export default (uuid: string, schedule: Data): Promise<Schedule> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/schedules${schedule.id ? `/${schedule.id}` : ''}`, {
            is_active: schedule.isActive,
            name: schedule.name,
            minute: schedule.cron.minute,
            hour: schedule.cron.hour,
            day_of_month: schedule.cron.dayOfMonth,
            month: schedule.cron.month,
            day_of_week: schedule.cron.dayOfWeek,
        })
            .then(({ data }) => resolve(rawDataToServerSchedule(data.attributes)))
            .catch(reject);
    });
};
