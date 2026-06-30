import StorageUtil from "../src/utils/StorageUtil";

export const addContact = async (db, contact) => {
    const insertQuery = `INSERT INTO Contacts (firstName,lastName,fullName,organization,title,telephone,email,
    flag,remark,serialNumber,visitTime,exhibitorID,visitDate,imgCard) 
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)`
    const exhibitorID = await StorageUtil.getUserId();
    const values = [
        contact.firstName,contact.lastName,contact.fullName,contact.organization,
        contact.title,contact.telephone,contact.email,contact.flag,contact.remark,
        contact.serialNumber,contact.visitTime,exhibitorID,contact.visitDate,contact.imgCard
    ]
    try{
        return db.executeSql(insertQuery,values)
    }catch (e) {
        console.error(e)
    }
}

export const getContacts = async (db, flag) => {
    try{
        const contacts = []
        const values = [await StorageUtil.getUserId()]
        let results = [];
        if(flag && flag.length > 0 && (flag === 'Cool' || flag === 'Warm' || flag === 'Hot' || flag === 'Neutral')){
            results = await db.executeSql("SELECT * FROM Contacts WHERE exhibitorID = ? and flag = ?", [await StorageUtil.getUserId(),flag])
        }else{
            results = await db.executeSql("SELECT * FROM Contacts WHERE exhibitorID = ?", [await StorageUtil.getUserId()])
        }
        results?.forEach((result) => {
            for(let index = 0; index < result.rows.length; index++){
                contacts.push(result.rows.item(index))
            }
        })
        return contacts
    }catch (e) {
        console.error(e)
    }
}

export const getContact = async (db, serialNumber, day) => {
    try{
        const contacts = {}
        const values = [await StorageUtil.getUserId()]
        let results = [];
        results = await db.executeSql("SELECT * FROM Contacts WHERE exhibitorID = ? and serialNumber = ? and visitDate = ?",
            [await StorageUtil.getUserId(), serialNumber, day])
        if (results && results.length > 0 && results[0].rows.length > 0) {
            return results[0].rows.item(0);
        }
        return contacts
    }catch (e) {
        console.error(e)
    }
}

export const updateContacts = async (db, contact) => {
    const updateQuery = `UPDATE Contacts SET firstName = ?, lastName = ?, fullName = ?, 
    organization = ?, title = ?, telephone = ?, email = ?, flag = ?, remark = ?, imgCard = ?, 
    serialNumber = ? WHERE id = ?`
    const values = [
        contact.firstName,contact.lastName,contact.fullName,contact.organization,
        contact.title,contact.telephone,contact.email,contact.flag,contact.remark
        ,contact.imgCard,contact.serialNumber,contact.id
    ]
    try{
        return db.executeSql(updateQuery,values)
    }catch (e) {
        console.error(e)
    }
}

export const deleteContact = async (db, contact) => {
    const deleteQuery = `DELETE FROM Contacts WHERE id = ?`
    const values = [contact.id]
    try{
        return db.executeSql(deleteQuery, values)
    }catch (e) {
        console.error(e)
    }
}

export const getReportByFlag = async (db) => {
    try{
        const contacts = []
        const values = [await StorageUtil.getUserId()]
        const results = await db.executeSql("SELECT flag,count(*) as count FROM Contacts WHERE exhibitorID = ? group by flag",values)
        results?.forEach((result) => {
            for(let index = 0; index < result.rows.length; index++){
                contacts.push(result.rows.item(index))
            }
        })
        return contacts
    }catch (e) {
        console.error(e)
    }
}

export const getContactsNum = async (db) => {
    try{
        const values = [await StorageUtil.getUserId()];
        const results = await db.executeSql("SELECT count(*) as count FROM Contacts WHERE exhibitorID = ?", values)
        return results?results[0].rows.item(0).count:0
    }catch (e) {
        console.error(e)
    }
}
