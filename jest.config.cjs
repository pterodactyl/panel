const { pathsToModuleNameMapper } = require('ts-jest');
const { compilerOptions } = require('./tsconfig');

/** @type {import('ts-jest').InitialOptionsTsJest} */
module.exports = {
    preset: 'ts-jest',
    globals: {
        'ts-jest': {
            isolatedModules: true,
        },
    },
    moduleFileExtensions: ['js', 'ts', 'tsx', 'd.ts', 'json', 'node'],
    moduleNameMapper: {
        '\\.(jpe?g|png|gif|svg)$': '<rootDir>/resources/scripts/__mocks__/file.ts',
        '\\.(s?css|less)$': 'identity-obj-proxy',
        ...pathsToModuleNameMapper(compilerOptions.paths, {
            prefix: '<rootDir>/',
        }),
    },
    setupFilesAfterEnv: [
        '<rootDir>/resources/scripts/setup-tests.ts',
    ],
    transform: {
        '.*\\.[t|j]sx$': 'babel-jest',
        '.*\\.ts$': 'ts-jest',
    },
    testPathIgnorePatterns: ['/node_modules/'],
};
