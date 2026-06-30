import React, {useCallback, useEffect} from 'react';
import {TabNavigatorScreen} from './main/main';
import {createStackNavigator} from "@react-navigation/stack";
import {connectToDatabase, createTables, DB_VER, removeTable} from "../db/db";
import {NavigationContainer} from "@react-navigation/native"; // 主页面路由导航
import ManualEntryScreen from './main/ManualEntryScreen';
import LoginScreen from "./login/LoginScreen";
import StorageUtil from "./utils/StorageUtil";
import {addSetting, getSetting, updateSetting} from "../db/settings";


const stack = createStackNavigator();
function StackScreen() {
    return (
        <stack.Navigator initialRouteName="Login">
            <stack.Screen name="Login" component={LoginScreen}/>
            <stack.Screen name="Main" component={TabNavigatorScreen} options={{
                headerShown: false
            }}/>
            <stack.Screen name="ManualEntry" component={ManualEntryScreen}/>
        </stack.Navigator>
    );
}

export default function App() {
    const loadData = useCallback(async () => {
        try{
            const db = await connectToDatabase()
            const setting = await getSetting(db);
            const ver = setting ? setting.version : 0;
            console.log('app', ver);
            if ( ver < DB_VER) {
                await removeTable(db,'Contacts');
                await createTables(db)
                if (setting) {
                    setting.version = DB_VER;
                    await updateSetting(db, setting);
                } else {
                    await addSetting(db, {version: DB_VER});
                }
            } else {
                await createTables(db)
            }
        }catch (e) {
            console.error(e)
        }
    }, [])

    useEffect(() => {
        loadData().then(()=>{})
    }, [loadData])
    return (
            <NavigationContainer>
                <StackScreen />
            </NavigationContainer>
    );
};
