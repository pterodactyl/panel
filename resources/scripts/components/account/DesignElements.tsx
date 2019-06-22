import * as React from 'react';

export default class DesignElements extends React.PureComponent {
    render () {
        return (
            <div className={'my-10'}>
                <div className={'flex'}>
                    <div className={'flex-1 mr-4'}>
                        <h2 className={'text-neutral-300 mb-2 px-4'}>A Special Announcement</h2>
                        <div className={'bg-neutral-700 p-4 rounded shadow-lg border-t-4 border-primary-400'}>
                            <p className={'text-neutral-200 text-sm'}>Your demands have been received: Dark Mode will be default in Pterodactyl 0.8!</p>
                        </div>
                    </div>
                    <div className={'ml-4 flex-1'}>
                        <h2 className={'text-neutral-300 mb-2 px-4'}>Form Elements</h2>
                        <div className={'bg-neutral-700 p-4 rounded shadow-lg border-t-4 border-primary-400'}>
                            <label className={'uppercase text-neutral-200'}>Email</label>
                            <input
                                type={'text'}
                                className={'w-full p-3 bg-neutral-600 border border-neutral-500 hover:border-neutral-400 text-sm rounded text-neutral-200 focus:shadow'}
                                style={{
                                    transition: 'border-color 150ms linear, box-shadow 150ms ease-in',
                                }}
                            />
                            <p className={'text-xs text-neutral-400 mt-2'}>
                                This is some descriptive helper text to explain how things work.
                            </p>
                            <div className={'mt-6'}/>
                            <label className={'uppercase text-neutral-200'}>Textarea</label>
                            <textarea
                                className={'w-full p-3 h-10 bg-neutral-600 border border-neutral-500 hover:border-neutral-400 text-sm rounded text-neutral-200 focus:shadow'}
                                style={{
                                    transition: 'border-color 150ms linear, box-shadow 150ms ease-in',
                                }}
                            ></textarea>
                            <div className={'mt-6'}/>
                            <button className={'tracking-wide bg-primary-500 spacing-wide text-xs text-primary-50 rounded p-3 uppercase border border-primary-600'}>
                                Button
                            </button>
                            <button className={'ml-2 tracking-wide bg-neutral-500 spacing-wide text-xs text-neutral-50 rounded p-3 uppercase border border-neutral-600'}>
                                Secondary
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}
