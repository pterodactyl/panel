import { rawDataToServerSchedule, Schedule } from '@/api/server/schedules/getServerSchedules';
import http from '@/api/http';

type Data = Pick<Schedule, 'cron' | 'name' | 'onlyWhenOnline' | 'isActive'> & { id?: number };

export default async (uuid: string, schedule: Data): Promise<Schedule> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/schedules${schedule.id ? `/${schedule.id}` : ''}`, {
        is_active: schedule.isActive,
        only_when_online: schedule.onlyWhenOnline,
        name: schedule.name,
        minute: schedule.cron.minute,
        hour: schedule.cron.hour,
        day_of_month: schedule.cron.dayOfMonth,
        month: schedule.cron.month,
        day_of_week: schedule.cron.dayOfWeek,
    });

    return rawDataToServerSchedule(data.attributes);
};
