const Allocation = function () {
    this.ip = null;
    this.port = null;
};

/**
 * Return a new allocation model.
 *
 * @param obj
 * @returns {Allocation}
 */
Allocation.prototype.fill = function (obj) {
    this.ip = obj.ip || null;
    this.port = obj.port || null;

    return this;
};

export default Allocation;
