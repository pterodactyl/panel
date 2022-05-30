import { FractalResponseData, FractalResponseList } from '@/api/http';

type Transformer<T> = (callback: FractalResponseData) => T;

const isList = (data: FractalResponseList | FractalResponseData): data is FractalResponseList => data.object === 'list';

function transform<T, M>(data: null | undefined, transformer: Transformer<T>, missing?: M): M;
function transform<T, M>(data: FractalResponseData | null | undefined, transformer: Transformer<T>, missing?: M): T | M;
function transform<T, M>(data: FractalResponseList | null | undefined, transformer: Transformer<T>, missing?: M): T[] | M;
function transform<T> (data: FractalResponseData | FractalResponseList | null | undefined, transformer: Transformer<T>, missing = undefined) {
    if (data === undefined || data === null) {
        return missing;
    }

    if (isList(data)) {
        return data.data.map(transformer);
    }

    if (!data || !data.attributes || data.object === 'null_resource') {
        return missing;
    }

    return transformer(data);
}

export { transform };
