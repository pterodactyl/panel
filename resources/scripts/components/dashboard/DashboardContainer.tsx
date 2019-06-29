import React from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faServer } from '@fortawesome/free-solid-svg-icons/faServer';
import { faMicrochip } from '@fortawesome/free-solid-svg-icons/faMicrochip';
import { faMemory } from '@fortawesome/free-solid-svg-icons/faMemory';
import { faHdd } from '@fortawesome/free-solid-svg-icons/faHdd';
import { faEthernet } from '@fortawesome/free-solid-svg-icons/faEthernet';
import { Link } from 'react-router-dom';

export default () => (
    <div className={'my-10'}>
        <Link to={'/server/e9d6c836'} className={'flex no-underline text-neutral-200 cursor-pointer items-center bg-neutral-700 p-4 border border-transparent hover:border-neutral-500'}>
            <div className={'rounded-full bg-neutral-500 p-3'}>
                <FontAwesomeIcon icon={faServer}/>
            </div>
            <div className={'w-1/2 ml-4'}>
                <p className={'text-lg'}>Party Parrots</p>
            </div>
            <div className={'flex flex-1 items-baseline justify-around'}>
                <div className={'flex ml-4'}>
                    <FontAwesomeIcon icon={faEthernet} className={'text-neutral-500'}/>
                    <p className={'text-sm text-neutral-400 ml-2'}>
                        192.168.100.100:25565
                    </p>
                </div>
                <div className={'flex ml-4'}>
                    <FontAwesomeIcon icon={faMicrochip} className={'text-neutral-500'}/>
                    <p className={'text-sm text-neutral-400 ml-2'}>
                        34.6%
                    </p>
                </div>
                <div className={'ml-4'}>
                    <div className={'flex'}>
                        <FontAwesomeIcon icon={faMemory} className={'text-neutral-500'}/>
                        <p className={'text-sm text-neutral-400 ml-2'}>
                            2094 MB
                        </p>
                    </div>
                    <p className={'text-xs text-neutral-600 text-center mt-1'}>of 4096 MB</p>
                </div>
                <div className={'ml-4'}>
                    <div className={'flex'}>
                        <FontAwesomeIcon icon={faHdd} className={'text-neutral-500'}/>
                        <p className={'text-sm text-neutral-400 ml-2'}>
                            278 MB
                        </p>
                    </div>
                    <p className={'text-xs text-neutral-600 text-center mt-1'}>of 16 GB</p>
                </div>
            </div>
        </Link>
        <div className={'flex mt-px cursor-pointer items-center bg-neutral-700 p-4 border border-transparent hover:border-neutral-500'}>
            <div className={'rounded-full bg-neutral-500 p-3'}>
                <FontAwesomeIcon icon={faServer}/>
            </div>
            <div className={'w-1/2 ml-4'}>
                <p className={'text-lg'}>My Factions Server</p>
                <p className={'text-neutral-400 text-xs mt-1'}>
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore
                    et dolore magna aliqua.
                </p>
            </div>
            <div className={'flex flex-1 items-baseline justify-around'}>
                <div className={'flex ml-4'}>
                    <FontAwesomeIcon icon={faEthernet} className={'text-neutral-500'}/>
                    <p className={'text-sm text-neutral-400 ml-2'}>
                        192.168.202.10:34556
                    </p>
                </div>
                <div className={'flex ml-4'}>
                    <FontAwesomeIcon icon={faMicrochip} className={'text-red-400'}/>
                    <p className={'text-sm text-white ml-2'}>
                        98.2 %
                    </p>
                </div>
                <div className={'ml-4'}>
                    <div className={'flex'}>
                        <FontAwesomeIcon icon={faMemory} className={'text-neutral-500'}/>
                        <p className={'text-sm text-neutral-400 ml-2'}>
                            376 MB
                        </p>
                    </div>
                    <p className={'text-xs text-neutral-600 text-center mt-1'}>of 1024 MB</p>
                </div>
                <div className={'ml-4'}>
                    <div className={'flex'}>
                        <FontAwesomeIcon icon={faHdd} className={'text-neutral-500'}/>
                        <p className={'text-sm text-neutral-400 ml-2'}>
                            187 MB
                        </p>
                    </div>
                    <p className={'text-xs text-neutral-600 text-center mt-1'}>of 32 GB</p>
                </div>
            </div>
        </div>
    </div>
);
