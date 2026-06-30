import React, {Component} from 'react';
import Toast from 'react-native-simple-toast';
import ListItem from '../views/ListItem';
import CommonTitleBar from '../views/CommonTitleBar';
import StorageUtil from '../utils/StorageUtil';
import {StackActions, NavigationActions} from 'react-navigation';
import BaseComponent from './BaseComponent';
import {Dimensions, Image, StyleSheet, View} from 'react-native';
import {strings} from '../language/locale';
import {useNavigation} from "@react-navigation/native";

const {width} = Dimensions.get('window');

export default class SettingsScreen extends BaseComponent {
  constructor(props) {
    super(props);
    this.state = {
      contactId: '',
      sendMsg: ''
    };
  }

  render() {
    return (
      <View style={styles.container}>
        <View style={styles.container}>
          <View style={{width: width, height: 20}}/>
          <ListItem icon={require('../../images/ic_settings.png')} text={strings.Logout} handleClick={() => {//注销
            this.logout()
          }}/>
        </View>
      </View>
    );
  }

  componentDidMount() {

  }

  componentWillUnmount() {

  }

  async logout() {
    await StorageUtil.delete('hasLogin');
    await StorageUtil.delete('user');
    await StorageUtil.delete('token');
    Toast.show(strings.Logoutsuccessful, 2);//注销成功
    // const resetAction = StackActions.reset({
    //   index: 0,
    //   actions: [
    //     NavigationActions.navigate({routeName: 'Login'})
    //   ]
    // });
    // this.props.navigation.dispatch(resetAction);
    // this.props.navigation.dispatch(StackActions.popToTop());
    // this.props.navigation.navigate('Login')
    this.props.navigation.reset({
      index: 0,
      routes: [{name: 'Login'}]
    });
  }
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    flexDirection: 'column'
  },
  input: {
    width: width,
  },
  soundImage: {
    width: 30,
    height: 30
  }
});
