import Checkbox from '@/components/elements/inputs/Checkbox';
import InputField from '@/components/elements/inputs/InputField';

const Input = Object.assign(
    {},
    {
        Text: InputField,
        Checkbox: Checkbox,
    },
);

export { Input };
export { default as styles } from './styles.module.css';
