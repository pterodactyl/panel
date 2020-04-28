import React, { useEffect, useState } from 'react';
import ReactGA from 'react-ga';
import getServerSchedules from '@/api/server/schedules/getServerSchedules';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import { RouteComponentProps } from 'react-router-dom';
import FlashMessageRender from '@/components/FlashMessageRender';
import ScheduleRow from '@/components/server/schedules/ScheduleRow';
import { httpErrorToHuman } from '@/api/http';
import EditScheduleModal from '@/components/server/schedules/EditScheduleModal';
import Can from '@/components/elements/Can';
import useServer from '@/plugins/useServer';
import useFlash from '@/plugins/useFlash';
import PageContentBlock from '@/components/elements/PageContentBlock';

export default ({ match, history }: RouteComponentProps) => {
    const { uuid } = useServer();
    const { clearFlashes, addError } = useFlash();
    const [ loading, setLoading ] = useState(true);
    const [ visible, setVisible ] = useState(false);

    const schedules = ServerContext.useStoreState(state => state.schedules.data);
    const setSchedules = ServerContext.useStoreActions(actions => actions.schedules.setSchedules);

    useEffect(() => {
        clearFlashes('schedules');
        getServerSchedules(uuid)
            .then(schedules => setSchedules(schedules))
            .catch(error => {
                addError({ message: httpErrorToHuman(error), key: 'schedules' });
                console.error(error);
            })
            .then(() => setLoading(false));
    }, []);

    useEffect(() => {
        ReactGA.pageview(location.pathname)
    }, []);

    return (
        <PageContentBlock>
            <FlashMessageRender byKey={'schedules'} className={'mb-4'}/>
            {(!schedules.length && loading) ?
                <Spinner size={'large'} centered={true}/>
                :
                <>
                    {
                        schedules.length === 0 ?
                            <p className={'text-sm text-center text-neutral-400'}>
                                There are no schedules configured for this server.
                            </p>
                            :
                            schedules.map(schedule => (
                                <a
                                    key={schedule.id}
                                    href={`${match.url}/${schedule.id}`}
                                    className={'grey-row-box cursor-pointer mb-2'}
                                    onClick={e => {
                                        e.preventDefault();
                                        history.push(`${match.url}/${schedule.id}`, { schedule });
                                    }}
                                >
                                    <ScheduleRow schedule={schedule}/>
                                </a>
                            ))
                    }
                    <Can action={'schedule.create'}>
                        <div className={'mt-8 flex justify-end'}>
                            {visible && <EditScheduleModal
                                appear={true}
                                visible={true}
                                onDismissed={() => setVisible(false)}
                            />}
                            <button
                                type={'button'}
                                className={'btn btn-sm btn-primary'}
                                onClick={() => setVisible(true)}
                            >
                                Create schedule
                            </button>
                        </div>
                    </Can>
                </>
            }
        </PageContentBlock>
    );
};
