import { Route, Routes, useNavigate } from 'react-router-dom';

import LoginContainer from '@/components/auth/LoginContainer';
import ForgotPasswordContainer from '@/components/auth/ForgotPasswordContainer';
import ResetPasswordContainer from '@/components/auth/ResetPasswordContainer';
import LoginCheckpointContainer from '@/components/auth/LoginCheckpointContainer';
import { NotFound } from '@/components/elements/ScreenBlock';

export default () => {
    const navigate = useNavigate();

    return (
        <div className="pt-8 xl:pt-32">
            <Routes>
                <Route path="login" element={<LoginContainer />} />
                <Route path="login/checkpoint/*" element={<LoginCheckpointContainer />} />
                <Route path="password" element={<ForgotPasswordContainer />} />
                <Route path="password/reset/:token" element={<ResetPasswordContainer />} />
                <Route path="*" element={<NotFound onBack={() => navigate('/auth/login')} />} />
            </Routes>
        </div>
    );
};
