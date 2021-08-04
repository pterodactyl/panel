import { lazy } from 'react';

/**
 * Custom features should be registered here as lazy components so that they do
 * not impact the generated JS bundle size. They will be automatically loaded in
 * whenever they are actually loaded for the client (which may be never, depending
 * on the feature and the egg).
 */
const EulaModalFeature = lazy(() => import(/* webpackChunkName: "feature.eula" */'@feature/eula/EulaModalFeature'));
const JavaVersionModalFeature = lazy(() => import(/* webpackChunkName: "feature.java_version" */'@feature/JavaVersionModalFeature'));

export { EulaModalFeature, JavaVersionModalFeature };
