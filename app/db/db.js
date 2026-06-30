import{
    enablePromise,
    openDatabase
} from "react-native-sqlite-storage"
enablePromise(true)

// 2: 2024.06.20 add visitDate in contact table
// 3: 2024.08.01 add imgCard, add setting tables
export const DB_VER = 3

export const connectToDatabase = async () => {
    return openDatabase(
        { name: "qss.db", location: "default"},
        () => {},
        (error) => {
            console.error(error)
            throw Error("Could not connect to database")
        }
    )
}

export const createTables = async (db) => {
    const contactsQuery = `
        CREATE TABLE IF NOT EXISTS Contacts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            firstName TEXT,
            lastName TEXT,
            fullName TEXT,
            organization TEXT,
            title TEXT,
            telephone TEXT,
            email TEXT,
            flag TEXT,
            remark TEXT,
            serialNumber TEXT,
            visitTime TEXT,
            visitDate TEXT,
            exhibitorID INTEGER,
            imgCard TEXT
        )
    `
    const settingsQuery = `
        CREATE TABLE IF NOT EXISTS Settings (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            version INTEGER
        )
    `
    try{
        db.executeSql(contactsQuery);
        db.executeSql(settingsQuery);
    }catch(error){
        console.error(error)
    }
}

export const getTableNames = async (db) => {
    try{
        const tableNames = []
        const results = await db.executeSql("SELECT name FROM sqlite_master WHERE type='table' " +
            "AND name NOT LIKE 'sqlite_%'")
        results?.forEach((result) => {
            for(let index = 0; index < result.rows.length; index++){
                tableNames.push(result.rows.item(index).name)
            }
        })
        return tableNames
    }catch (e) {
        console.error(e)
    }
}

export const removeTable = async (db, tableName) => {
    const query = `DROP TABLE IF EXISTS ${tableName}`
    try{
        await db.executeSql(query)
    }catch (e) {
        console.error(e)
    }
}
