export const addSetting = async (db, cfg) => {
    const insertQuery = `INSERT INTO Settings (version) VALUES (?)`
    const values = [cfg.version]
    try{
        return db.executeSql(insertQuery,values)
    }catch (e) {
        console.error(e)
    }
}

export const getSetting = async (db) => {
    try{
        let results = [];
        results = await db.executeSql("SELECT * FROM Settings WHERE id = 1")
        if (results && results.length > 0 && results[0].rows.length > 0) {
            return results[0].rows.item(0);
        }
        return null;
    }catch (e) {
        console.error(e)
        return null;
    }
}

export const updateSetting = async (db, cfg) => {
    const updateQuery = `UPDATE Settings SET version = ? WHERE id = ?`
    const values = [cfg.version,cfg.id]
    try{
        return db.executeSql(updateQuery,values)
    }catch (e) {
        console.error(e)
    }
}

export const deleteSetting = async (db, cfg) => {
    const deleteQuery = `DELETE FROM Settings WHERE id = ?`
    const values = [cfg.id]
    try{
        return db.executeSql(deleteQuery, values)
    }catch (e) {
        console.error(e)
    }
}

