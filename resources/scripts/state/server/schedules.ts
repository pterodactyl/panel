import { action, Action } from 'easy-peasy';
import { Schedule } from '@/api/server/schedules/getServerSchedules';

export interface ServerScheduleStore {
    data: Schedule[];
    setSchedules: Action<ServerScheduleStore, Schedule[]>;
    appendSchedule: Action<ServerScheduleStore, Schedule>;
    removeSchedule: Action<ServerScheduleStore, number>;
}

const schedules: ServerScheduleStore = {
    data: [],

    setSchedules: action((state, payload) => {
        state.data = payload;
    }),

    appendSchedule: action((state, payload) => {
        if (state.data.find(schedule => schedule.id === payload.id)) {
            state.data = state.data.map(schedule => (schedule.id === payload.id ? payload : schedule));
        } else {
            state.data = [...state.data, payload];
        }
    }),

    removeSchedule: action((state, payload) => {
        state.data = [...state.data.filter(schedule => schedule.id !== payload)];
    }),
};

export default schedules;
