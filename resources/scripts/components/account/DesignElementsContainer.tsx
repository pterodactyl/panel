import * as React from 'react';
import { Link } from 'react-router-dom';
import ContentBox from '@/components/elements/ContentBox';

export default class DesignElementsContainer extends React.PureComponent {
    render () {
        return (
            <React.Fragment>
                <div className={'my-10'}>
                    <div className={'flex'}>
                        <ContentBox
                            className={'flex-1 mr-4'}
                            title={'A Special Announcement'}
                            borderColor={'border-primary-400'}
                        >
                            <p className={'text-neutral-200 text-sm'}>
                                Your demands have been received: Dark Mode will be default in Pterodactyl 0.8!
                            </p>
                            <p><Link to={'/'}>Back</Link></p>
                        </ContentBox>
                        <div className={'ml-4 flex-1'}>
                            <h2 className={'text-neutral-300 mb-2 px-4'}>Form Elements</h2>
                            <div className={'bg-neutral-700 p-4 rounded shadow-lg border-t-4 border-primary-400'}>
                                <label className={'uppercase text-neutral-200'}>Email</label>
                                <input type={'text'} className={'input-dark'}/>
                                <p className={'input-help'}>
                                    This is some descriptive helper text to explain how things work.
                                </p>
                                <div className={'mt-6'}/>
                                <label className={'uppercase text-neutral-200'}>Username</label>
                                <input type={'text'} className={'input-dark error'}/>
                                <p className={'input-help'}>
                                    This field has an error.
                                </p>
                                <div className={'mt-6'}/>
                                <label className={'uppercase text-neutral-200'}>Disabled Field</label>
                                <input type={'text'} className={'input-dark'} disabled={true}/>
                                <div className={'mt-6'}/>
                                <label className={'uppercase text-neutral-200'}>Textarea</label>
                                <textarea className={'input-dark h-32'}></textarea>
                                <div className={'mt-6'}/>
                                <button className={'btn btn-primary btn-sm'}>
                                    Blue
                                </button>
                                <button className={'btn btn-grey btn-sm ml-2'}>
                                    Grey
                                </button>
                                <button className={'btn btn-green btn-sm ml-2'}>
                                    Green
                                </button>
                                <button className={'btn btn-red btn-sm ml-2'}>
                                    Red
                                </button>
                                <div className={'mt-6'}/>
                                <button className={'btn btn-secondary btn-sm'}>
                                    Secondary
                                </button>
                                <button className={'btn btn-secondary btn-red btn-sm ml-2'}>
                                    Secondary Danger
                                </button>
                                <div className={'mt-6'}/>
                                <button className={'btn btn-primary btn-lg'}>
                                    Large
                                </button>
                                <button className={'btn btn-primary btn-xs ml-2'}>
                                    Tiny
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </React.Fragment>
        );
    }
}
