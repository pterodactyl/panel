import { Collection, Model } from 'vue-mc';

/**
 * A generic server model used throughout the code base.
 */
export class Server extends Model {
    /**
     * Identifier the primary identifier for this model.
     *
     * @returns {{identifier: string}}
     */
    static options() {
        return {
            identifier: 'identifier',
        };
    }

    /**
     * Return the defaults for this model.
     *
     * @returns {object}
     */
    static defaults() {
        return {
            uuid: null,
            identifier: null,
            name: '',
            description: '',
            node: '',
            limits: {
                memory: 0,
                swap: 0,
                disk: 0,
                io: 0,
                cpu: 0,
            },
            allocation: {
                ip: null,
                port: null,
            },
            feature_limits: {
                databases: 0,
                allocations: 0,
            },
        };
    }

    /**
     * Mutations to apply to items in this model.
     *
     * @returns {{name: StringConstructor, description: StringConstructor}}
     */
    static mutations() {
        return {
            uuid: String,
            identifier: String,
            name: String,
            description: String,
            node: String,
            limits: {
                memory: Number,
                swap: Number,
                disk: Number,
                io: Number,
                cpu: Number,
            },
            allocation: {
                ip: String,
                port: Number,
            },
            feature_limits: {
                databases: Number,
                allocations: Number,
            }
        };
    }

    /**
     * Routes to use when building models.
     *
     * @returns {{fetch: string}}
     */
    static routes() {
        return {
            fetch: '/api/client/servers/{identifier}',
        };
    }
}

export class ServerCollection extends Collection {
    static model() {
        return Server;
    }

    static defaults() {
        return {
            orderBy: identifier,
        };
    }

    static routes() {
        return {
            fetch: '/api/client',
        };
    }

    get todo() {
        return this.sum('done');
    }

    get done() {
        return this.todo === 0;
    }
}
