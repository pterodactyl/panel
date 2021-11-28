import React from 'react';
import Modal, { RequiredModalProps } from '@/components/elements/Modal';
import { State, useStoreState } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import ContentBox from '@/components/elements/ContentBox';
import tw from 'twin.macro';
import { LinkButton } from '@/components/elements/Button';

export default ({ ...props }: RequiredModalProps) => {
    const { drivers } = useStoreState<ApplicationStore, any>(state => state.settings.data!.oauth);
    const oauth = JSON.parse(useStoreState((state: State<ApplicationStore>) => state.user.data!.oauth));

    return (
        <Modal
            {...props}
        >
            {JSON.parse(drivers).map((driver: string) => (
                <ContentBox
                    key={driver}
                    css={tw`mx-4 my-4 inline-block`}
                >
                    <div css={tw`w-48`}>
                        <img src={'/assets/svgs/' + driver + '.svg'} css={tw`w-16 float-right`} alt={driver}/>
                        <h3>{driver.charAt(0).toUpperCase() + driver.slice(1)}</h3>

                        {oauth[driver] == null ?
                            <div css={tw`mt-16 mb-4`}>
                                <LinkButton
                                    isSecondary
                                    href={`/account/oauth/link?driver=${driver}`}
                                >
                                    Link {driver}
                                </LinkButton>
                            </div>
                            :
                            <div>
                                <div css={tw`mt-8 mb-4`}>
                                    Linked with id <code>{oauth[driver]}</code>
                                </div>
                                <div css={tw`mb-4`}>
                                    <LinkButton
                                        color={'red'}
                                        isSecondary
                                        href={`/account/oauth/unlink?driver=${driver}`}
                                    >
                                        Unlink {driver}
                                    </LinkButton>
                                </div>
                            </div>
                        }
                    </div>
                </ContentBox>
            ))}
        </Modal>
    );
};
