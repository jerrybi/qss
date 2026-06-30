import React from 'react';
import {createBottomTabNavigator} from "@react-navigation/bottom-tabs";
import Icon from "react-native-vector-icons/MaterialCommunityIcons";
import SettingsScreen from "./SettingsScreen";
import ScanScreen from "./ScanScreen";
import SyncScreen from './SyncScreen';
import HomeScreen from './HomeScreen';
import CommonTitleBar from "../views/CommonTitleBar";

const Tab = createBottomTabNavigator();

export function TabNavigatorScreen() {
    return (
        <Tab.Navigator screenOptions={{
            tabBarActiveTintColor: '#283560',
            tabBarInactiveTintColor: '#999999',
            headerTitleAlign: 'center'
        }}>
            <Tab.Screen name="Home" component={HomeScreen} options={{
                tabBarLabel: 'Home',
                tabBarIcon: ({color, size}) => (<Icon name="home" color={color} size={size}/>)
            }}/>
            <Tab.Screen name="Scan" component={ScanScreen} options={{
                tabBarLabel: 'Scan',
                tabBarIcon: ({color, size}) => (<Icon name="qrcode-scan" color={color} size={size}/>)
            }}/>
            <Tab.Screen name="Sync" component={SyncScreen} options={{
                tabBarLabel: 'Sync',
                tabBarIcon: ({color, size}) => (<Icon name="sync" color={color} size={size}/>),
                // headerTitle: () => <CommonTitleBar isBack={false} title={'Sync'}
                //    rightIcon={require('../../images/filter.png')} handleRightClick={}/>
            }}/>
            <Tab.Screen name="Settings" component={SettingsScreen} options={{
                tabBarLabel: 'Settings',
                tabBarIcon: ({color, size}) => (<Icon name="cog" color={color} size={size}/>)
            }}/>
        </Tab.Navigator>
    );
}
