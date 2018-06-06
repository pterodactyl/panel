export default class Server {
    constructor({
        identifier,
        uuid,
        name,
        node,
        description,
        allocation,
        limits,
        feature_limits
    }) {
        this.identifier = identifier;
        this.uuid = uuid;
        this.name = name;
        this.node = node;
        this.description = description;
        this.allocation = allocation;
        this.limits = limits;
        this.feature_limits = feature_limits;
    }
}
