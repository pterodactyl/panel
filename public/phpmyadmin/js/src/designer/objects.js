// eslint-disable-next-line no-unused-vars
var DesignerObjects = {
    PdfPage: function (dbName, pageDescr, tblCords) {
        // no dot set the auto increment before put() in the database
        // issue #12900
        // eslint-disable-next-line no-unused-vars
        var pgNr;
        this.dbName = dbName;
        this.pageDescr = pageDescr;
        this.tblCords = tblCords;
    },
    TableCoordinate: function (dbName, tableName, pdfPgNr, x, y) {
        // no dot set the auto increment before put() in the database
        // issue #12900
        // var id;
        this.dbName = dbName;
        this.tableName = tableName;
        this.pdfPgNr = pdfPgNr;
        this.x = x;
        this.y = y;
    }
};
