import React, { useEffect, useState } from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { Form, Formik, FormikHelpers } from 'formik';
import { object, string } from 'yup';
import Field from '@/components/elements/Field';
import getTwoFactorTokenUrl from '@/api/account/getTwoFactorTokenUrl';
import enableAccountTwoFactor from '@/api/account/enableAccountTwoFactor';
import FlashMessageRender from '@/components/FlashMessageRender';
import {Actions, State, useStoreActions, useStoreState} from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { httpErrorToHuman } from '@/api/http';
import ContentBox from "@/components/elements/ContentBox";

interface Values {
    code: string;
}

export default ({ ...props }: RequiredModalProps) => {
    const { drivers } = useStoreState<ApplicationStore, any>(state => state.settings.data!.oauth);
    const oauth = JSON.parse(useStoreState((state: State<ApplicationStore>) => state.user.data!.oauth));

    return (
        <Modal
            {...props}
        >
            {JSON.parse(drivers).map((driver: string) => (
                <ContentBox
                    className={'mt-8 md:mt-0 mx-4 my-4 inline-block'}
                >
                    <div className={"w-48"}>
                        <img src={'/assets/svgs/' + driver + '.svg'} className={'w-16 float-right'} alt={driver}/>
                        <h3>{driver.charAt(0).toUpperCase() + driver.slice(1)}</h3>

                        { oauth[driver] != undefined &&
                        <div>
                            <div className={"mt-8 mb-4"}>
                                Linked with id <code>{oauth[driver]}</code>
                            </div>
                            <div className={"mb-4"}>
                                <a className={"btn btn-red btn-secondary btn-sm"} href={'/account/oauth/unlink?driver=' + driver}>
                                    Unlink {driver}
                                </a>
                            </div>
                        </div>
                        }
                        { oauth[driver] == undefined &&
                        <div className={"mt-16 mb-4"}>
                            <a className={"btn btn-green btn-secondary btn-sm"} href={'/account/oauth/link?driver=' + driver}>
                                Link {driver}
                            </a>
                        </div>
                        }

                    </div>
                </ContentBox>
            ))}
        </Modal>
    );
};
