import Allocation from './allocation';

const Server = function () {
    this.identifier = null;
    this.uuid = null;
    this.name = '';
    this.description = '';
    this.allocation = null;
    this.limits = {
        memory: 0,
        swap: 0,
        disk: 0,
        io: 0,
        cpu: 0,
    };
    this.feature_limits = {
        databases: 0,
        allocations: 0,
    };
};

/**
 * Return a new server model filled with data from the provided object.
 *
 * @param {object} obj
 * @returns {Server}
 */
Server.prototype.fill = function (obj) {
    this.identifier = obj.identifier;
    this.uuid = obj.uuid;
    this.name = obj.name;
    this.description = obj.description;
    this.allocation = new Allocation().fill(obj.allocation || {});
    this.limits = obj.limits;
    this.feature_limits = obj.feature_limits;

    return this;
};

export default Server;
