// NavigationActions is super critical
import { NavigationActions, StackNavigator } from 'react-navigation'
// these are literally whatever you want, standard components
// but, they are sitting in the root of the stack
import Splash from '../components/Auth/Splash'
import SignUp from '../components/Auth/SignupForm'
import SignIn from '../components/Auth/LoginForm'
import ForgottenPassword from '../components/Auth/ForgottenPassword'
// this is an example of a nested view, you might see after logging in
import Dashboard from '../components/Dashboard'
import {createBottomTabNavigator} from "@react-navigation/bottom-tabs";
import HomeScreen from "../src/screens/HomeScreen";
import Icon from "react-native-vector-icons/MaterialCommunityIcons";
import ScanScreen from "../src/screens/ScanScreen";
import SyncScreen from "../src/screens/SyncScreen";
import SettingsScreen from "../src/screens/SettingsScreen";
import {createStackNavigator} from "@react-navigation/stack";
import {useSelector} from "react-redux";
import ManualEntryScreen from "../src/screens/ManualEntryScreen";
import LoginScreen from "../src/login/LoginScreen";
import React from "react"; // index.js file

const Tab = createBottomTabNavigator();

function TabNavigatorScreen() {
    return (
        <Tab.Navigator screenOptions={{
            tabBarActiveTintColor: '#45C018',
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
                tabBarIcon: ({color, size}) => (<Icon name="sync" color={color} size={size}/>)
            }}/>
            <Tab.Screen name="Settings" component={SettingsScreen} options={{
                tabBarLabel: 'Settings',
                tabBarIcon: ({color, size}) => (<Icon name="cog" color={color} size={size}/>)
            }}/>
        </Tab.Navigator>
    );
}

const WeLoggedIn = createStackNavigator();
function WeLoggedInScreen() {
    return (
        <WeLoggedIn.Navigator>
            <WeLoggedIn.Screen name="Main" component={TabNavigatorScreen} options={{
                headerShown: false
            }}/>
            <WeLoggedIn.Screen name="ManualEntry" component={ManualEntryScreen}/>
        </WeLoggedIn.Navigator>
    );
}

const stack = createStackNavigator();
function StackScreen() {
    console.log('StackScreen')
    const hasLogin = useSelector((state)=>state.hasLogin);
    console.log('hasLogin:'+hasLogin)
    return (
        <stack.Navigator>
            {hasLogin ? (
                <>
                    <stack.Screen name="Main" component={TabNavigatorScreen} options={{
                        headerShown: false
                    }}/>
                    <stack.Screen name="ManualEntry" component={ManualEntryScreen}/>
                </>
            ) : (
                <>
                    <stack.Screen name="Login" component={LoginScreen}/>
                </>
            )
            }
        </stack.Navigator>
    );
}

// notice we are exporting this one. this turns into <RootNavigationStack />
// in your src/App.js file.
export const NavigationStack = StackNavigator({
    Login: {
        screen: SignIn
    },
    WeLoggedIn: {
        screen: WeLoggedIn  // Notice how the screen is a StackNavigator
    }                       // now you understand how it works!
}, {
    headerMode: 'none'
})

// this is super critical for everything playing nice with Redux
// did you read the React-Navigation docs and recall when it said
// most people don't hook it up correctly? well, yours is now correct.
// this is translating your state properly into Redux on initialization
const INITIAL_STATE = NavigationStack.router.getStateForAction(NavigationActions.init())

// this is pretty much a standard reducer, but it looks fancy
// all it cares about is "did the navigation stack change?"
// if yes => update the stack
// if no => pass current stack through
export default (state = INITIAL_STATE, action) => {
    const nextState = NavigationStack.router.getStateForAction(action, state)

    return nextState || state
}
