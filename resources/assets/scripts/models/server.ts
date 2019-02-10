type ServerAllocation = {
    ip: string,
    port: number,
};

type ServerLimits = {
    memory: number,
    swap: number,
    disk: number,
    io: number,
    cpu: number,
}

type ServerFeatureLimits = {
    databases: number,
    allocations: number,
};

export type ServerData = {
    identifier: string,
    uuid: string,
    name: string,
    node: string,
    description: string,
    allocation: ServerAllocation,
    limits: ServerLimits,
    feature_limits: ServerFeatureLimits,
};

/**
 * A model representing a server returned by the client API.
 */
export default class Server {
    /**
     * The server identifier, generally the 8-character representation of the server UUID.
     */
    identifier: string;

    /**
     * The long form identifier for this server.
     */
    uuid: string;

    /**
     * The human friendy name for this server.
     */
    name: string;

    /**
     * The name of the node that this server belongs to.
     */
    node: string;

    /**
     * A description of this server.
     */
    description: string;

    /**
     * The primary allocation details for this server.
     */
    allocation: ServerAllocation;

    /**
     * The base limits for this server when it comes to the actual docker container.
     */
    limits: ServerLimits;

    /**
     * The feature limits for this server, database & allocations currently.
     */
    featureLimits: ServerFeatureLimits;

    /**
     * Construct a new server model instance.
     */
    constructor(data: ServerData) {
        this.identifier = data.identifier;
        this.uuid = data.uuid;
        this.name = data.name;
        this.node = data.node;
        this.description = data.description;
        this.allocation = data.allocation;
        this.limits = data.limits;
        this.featureLimits = data.feature_limits;
    }
}
