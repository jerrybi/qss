import React, {Component} from 'react';
import Toast from 'react-native-simple-toast';
import ListItem from '../views/ListItem';
import CommonTitleBar from '../views/CommonTitleBar';
import StorageUtil from '../utils/StorageUtil';
import {NavigationActions} from 'react-navigation';
import BaseComponent from './BaseComponent';
import {
  Dimensions,
  FlatList,
  Image, Modal,
  NativeEventEmitter,
  Platform,
  StyleSheet,
  Text, TextInput,
  TouchableHighlight, TouchableOpacity, TouchableWithoutFeedback,
  View,
} from 'react-native';
import {connectToDatabase} from "../../db/db";
import {addContact, getContacts, updateContacts} from "../../db/contacts";
import Http from "../utils/Http";
import Utils from "../utils/Utils";
import {strings} from "../language/locale";
import LoadingView from "../views/LoadingView";
import singleCountEmitter from "../event/CountEmitter";
import ImageViewer from "react-native-image-zoom-viewer";

const {width} = Dimensions.get('window');

export default class SettingsScreen extends BaseComponent {
  constructor(props) {
    super(props);
    console.log(props)
    props.navigation.setOptions({
      headerTitle: () => <CommonTitleBar isBack={false} title={'Sync'}
         rightIcon={require('../../images/filter.png')} handleRightClick={() => this.clickFilter()}/>
    });
    this.state = {
      contactId: '',
      sendMsg: '',
      contacts:[],
      contact:{},
      isModalVisible:false,
      lastUpdateTime:'',
      showProgress: false,
      flag: props.route && props.route.params ? props.route.params.flag : undefined,
      isFilterModalVisible: false,
      showPictureModal:false,
      curImgCard:undefined
    };
  }

  render() {
    const {contacts,isModalVisible,contact,lastUpdateTime,isFilterModalVisible,flag} = this.state
    console.log('isModalVisible:'+isModalVisible)
    return (
        <View style={styles.container}>
          <View style={styles.container}>
            <FlatList
                ItemSeparatorComponent={
                  Platform.OS !== 'android' &&
                  (({highlighted}) => (
                      <View
                          style={[styles.separator, highlighted && {marginLeft: 0}]}
                      />
                  ))
                }
                data={contacts}
                renderItem={({item, index, separators}) => (
                    <View key={item.id} style={styles.contact}>
                      <View style={styles.itemLine}>
                        <Text style={styles.contactInfo}>{item.serialNumber}</Text>
                        <Text style={styles.contactInfo}>{item.visitTime}</Text>
                      </View>
                      <View style={styles.itemLine}>
                        <Text style={styles.contactInfo}>{item.firstName} {item.lastName}</Text>
                        <Text style={styles.contactInfo}>{item.title}</Text>
                      </View>
                      <Text style={styles.contactInfo}>{item.organization}</Text>
                      <View style={styles.itemLine}>
                        <Text style={styles.contactInfo}>{item.telephone}</Text>
                        <Text style={styles.contactInfo}>{item.email}</Text>
                      </View>
                      {item.imgCard ? <TouchableWithoutFeedback onPress={()=>this.handleShowModal(item.imgCard)}>
                            <Image source={{uri: 'data:image/png;base64,' + item.imgCard}} style={styles.imgCard}/>
                          </TouchableWithoutFeedback>
                        : null}
                      <View style={styles.item}>
                        <View>
                          {
                            item.flag == 'Cool' ? <Text style={[styles.remarkBtn,styles.cool]}>Cool</Text>
                                : (item.flag == 'Warm' ? <Text style={[styles.remarkBtn,styles.warm]}>Warm</Text>
                                : (item.flag == 'Hot' ? <Text style={[styles.remarkBtn,styles.hot]}>Hot</Text>
                                    : <></>))
                          }
                          <Text style={styles.remarkLabel}>{item.remark}</Text>
                        </View>
                        <TouchableOpacity onPress={()=>this._onPress(item)}>
                          <Image style={styles.edit} source={require('../../images/edit.png')}/>
                        </TouchableOpacity>
                      </View>
                    </View>
                )}
            />
            <View style={styles.bottom}>
              <Text style={styles.lastUpdate}>Last update at: {lastUpdateTime}</Text>
              <TouchableOpacity
                  style={[styles.btn,styles.submit]}
                  onPress={()=>this.syncData()}>
                <Text>Sync</Text>
              </TouchableOpacity>
            </View>
          </View>
          <Modal visible={isModalVisible}
                  animationType="slide"
                 transparent={true}
                 onRequestClose={() => {
                   this.setState({
                     isModalVisible:false
                   })
                 }}
          >
            <View style={styles.scanResultContainer}>
              <View style={styles.scanResult}>
                <TouchableOpacity style={styles.closeIcon}
                                  onPress={()=>{this.setState({
                                    isModalVisible:false
                                  })
                                  }}>
                  <Image source={require('../../images/delete.png')}
                         style={styles.delImg}/>
                </TouchableOpacity>
                <View style={styles.remarkContainer}>
                  <TouchableOpacity style={[styles.remarkBtn,styles.cool,contact.flag === 'Cool'?styles.flagActive:null]}
                                    onPress={()=>{this.onPressFlag('Cool')}}>
                    <Text style={styles.flagText}>Cool</Text>
                  </TouchableOpacity>
                  <TouchableOpacity style={[styles.remarkBtn,styles.warm,contact.flag === 'Warm'?styles.flagActive:null]}
                                    onPress={()=>{this.onPressFlag('Warm')}}>
                    <Text style={styles.flagText}>Warm</Text>
                  </TouchableOpacity>
                  <TouchableOpacity style={[styles.remarkBtn,styles.hot,contact.flag === 'Hot'?styles.flagActive:null]}
                                    onPress={()=>{this.onPressFlag('Hot')}}>
                    <Text style={styles.flagText}>Hot</Text>
                  </TouchableOpacity>
                </View>
                <View style={styles.remarkText}>
                  <Text style={styles.remarkTitle}>Remarks</Text>
                  <TextInput
                      style={styles.remark}
                      onChangeText={text => this.onChangeRemark(text)}
                      value={contact.remark}
                      multiline={true}
                      numberOfLines={5}
                      placeholder="Please enter remark"
                      placeholderTextColor={'#aaa'}
                  />
                </View>
                <TouchableOpacity
                    style={[styles.btn,styles.submit]}
                    onPress={() => {this.update()}}
                >
                  <Text>Update</Text>
                </TouchableOpacity>
              </View>
            </View>
          </Modal>
          <Modal visible={isFilterModalVisible}
                 animationType="slide"
                 transparent={true}
                 onRequestClose={() => {
                   this.setState({
                     isFilterModalVisible:false
                   })
                 }}
          >
            <View style={styles.scanResultContainer}>
              <View style={styles.filterResult}>
                  <TouchableOpacity
                      style={[styles.filterItem,flag === 'Cool'?styles.filterActive:null]}
                      onPress={() => {this.clickFilterItem('Cool')}}
                  >
                    <Text style={styles.filterText}>Cool</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                      style={[styles.filterItem,flag === 'Warm'?styles.filterActive:null]}
                      onPress={() => {this.clickFilterItem('Warm')}}
                  >
                    <Text style={styles.filterText}>Warm</Text>
                  </TouchableOpacity>
                  <TouchableOpacity
                      style={[styles.filterItem,flag === 'Hot'?styles.filterActive:null]}
                      onPress={() => {this.clickFilterItem('Hot')}}
                  >
                    <Text style={styles.filterText}>Hot</Text>
                  </TouchableOpacity>
                <TouchableOpacity
                    style={[styles.filterItem,flag === 'Neutral'?styles.filterActive:null]}
                    onPress={() => {this.clickFilterItem('Neutral')}}
                >
                  <Text style={styles.filterText}>Neutral</Text>
                </TouchableOpacity>
                  <TouchableOpacity
                      style={[styles.filterItem,flag === 'All'?styles.filterActive:null]}
                      onPress={() => {this.clickFilterItem('All')}}
                  >
                    <Text style={styles.filterText}>All</Text>
                  </TouchableOpacity>
              </View>
            </View>
          </Modal>
          {
            this.state.showProgress ? (
                <LoadingView cancel={() => this.setState({showProgress: false})}/>
            ) : (null)
          }
          <Modal visible={this.state.showPictureModal} transparent={true}>
            <ImageViewer
                imageUrls={[
                  {
                    url: 'data:image/png;base64,' + this.state.curImgCard,
                  },
                ]}
                onClick={() => this.handleHideModel()}
            />
          </Modal>
        </View>
    );
  }

  componentDidMount() {
    console.log('SyncScreen componentDidMount')
    singleCountEmitter.addListener('contacts_update',event=>{
      this.fetchContacts()
    })
    singleCountEmitter.addListener('flag_update',p => {
      this.setState({
        flag: p.flag
      });
      this.fetchContacts();
    })
    this.fetchContacts()
    StorageUtil.get('lastUpdate').then(res => {
      this.setState({
        lastUpdateTime: res ? res : ''
      });
    });
  }

  componentWillUnmount() {
    singleCountEmitter.removeAllListeners('contacts_update')
    singleCountEmitter.removeAllListeners('flag_update')
  }

  fetchContacts(){
    connectToDatabase().then(db => {
      console.log('fetchContacts',this.state.flag)
      getContacts(db,this.state.flag).then(r => {
        console.log(r)
        this.setState({
          contacts:r
        })
      })
    })
  }

  _onPress(item){
    this.setState({
      isModalVisible:true,
      contact:item
    })
  }

  onChangeRemark(text){
    let {contact} = this.state;
    contact.remark = text
    this.setState({
      contact:contact
    })
  }

  onPressFlag(flag){
    let {contact} = this.state;
    contact.flag = flag
    this.setState({
      contact:contact
    })
  }

  update(){
    connectToDatabase().then((db) => {
      console.log(db)
      let {contact} = this.state;
      updateContacts(db, contact).then(()=>{
        singleCountEmitter.emit('contacts_update')
        this.setState({
          isModalVisible:false
        })
      })
    });
  }

  syncData(){
    this.setState({showProgress: true});
    console.log(this.state.contacts)
    Http.post('api/uploadContact',{
      contacts: this.state.contacts
    },'json').then((json) => {
      let lastUpdate = json.data.lastUpdate ? json.data.lastUpdate : '';
      this.setState({showProgress: false,lastUpdateTime:lastUpdate});
      console.log(json)
      StorageUtil.set('lastUpdate', lastUpdate);
      Toast.show(strings.SyncOk, 2);
    }).catch((e) => {
      this.setState({showProgress: false});
      console.log(e);
      if(e.status == 401){
        this.props.navigation.reset({
          index: 0,
          routes: [{name: 'Login'}]
        });
      }
    });
  }

  clickFilter(){
    this.setState({
      isFilterModalVisible: true
    })
  }

  clickFilterItem(flag){
    this.setState({
      flag: flag,
      isFilterModalVisible: false
    })
    this.fetchContacts()
  }

  handleShowModal(imgCard) {
    this.setState({
      showPictureModal: true,
      curImgCard: imgCard
    })
  }

  handleHideModel() {
    this.setState({
      showPictureModal: false
    })
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
  },
  separator:{
    width:'100%',
    height:1,
    backgroundColor:'#ddd'
  },
  contact:{
    backgroundColor:'#fff',
    flex:1,
    flexDirection: 'column',
    justifyContent: 'center',
    alignItems: 'flex-start',
    padding:10,
    marginTop:5
  },
  itemLine:{
    width:'100%',
    flexDirection:'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  contactInfo:{
    color:'#000'
  },
  remarkBtn:{
    borderRadius: 8,
    width: 60,
    padding: 2,
    alignItems:'center',
    justifyContent:'center',
    textAlign:'center',
    textAlignVertical:'center'
  },
  scanResultContainer:{
    flex:1,
    justifyContent:'center',
    alignItems:'center'
  },
  scanResult:{
    width: '80%',
    height: 250,
    flexDirection:'column',
    alignItems:'center',
    justifyContent:'flex-start',
    backgroundColor:"#ccc",
    borderRadius: 8,
    padding:20,
    position: 'relative'
  },
  flagActive:{
    transform:[
      {
        scaleY: 1.2
      }
    ],
    borderColor: '#18864b',
    borderWidth: 2
  },
  cool: {
    backgroundColor: '#0098e8',
  },
  warm: {
    backgroundColor: '#ffb621',
  },
  hot: {
    backgroundColor: '#ff0000',
  },
  remarkContainer: {
    width:'100%',
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center'
  },
  remarkText:{
    width:'100%',
    flex: 1,
    flexDirection: 'column',
    justifyContent: 'center',
    alignItems: 'flex-start'
  },
  remarkTitle:{
    color:'#000'
  },
  remarkLabel:{
    color:'#000'
  },
  edit:{
    width:20,
    height:20
  },
  item:{
    width:'100%',
    flexDirection:'row',
    justifyContent:'space-between',
    alignItems:'center'
  },
  remark: {
    width: '100%',
    height: 80,
    borderColor: '#eee',
    borderWidth: 1,
    alignItems:'flex-start',
    textAlignVertical:'top',
    color:'#000'
  },
  btn:{
    width: '40%',
    height: 40,
    alignItems:'center',
    justifyContent:'center',
    borderRadius:8
  },
  submit:{
    backgroundColor:'#1e80ff',
    width: 100
  },
  bottom:{
    height:60,
    width:'90%',
    marginLeft:'5%',
    flexDirection:'row',
    justifyContent:'space-between',
    alignItems:'center'
  },
  lastUpdate:{
    color:'#aaa'
  },
  flagText:{
    fontSize: 18,
    fontWeight: 'bold'
  },
  delImg:{
    width: 20,
    height: 20
  },
  closeIcon:{
    position: 'absolute',
    top: -10,
    right: -10
  },
  filterItem:{
    width: '100%',
    height: 40,
    alignItems:'center',
    justifyContent: 'center'
  },
  filterActive:{
    backgroundColor: '#283560',
    color: '#ffffff'
  },
  filterResult:{
    width: '50%',
    flexDirection:'column',
    alignItems:'center',
    justifyContent:'flex-start',
    backgroundColor:"#000",
    borderRadius: 8,
    padding:10,
    position: 'relative'
  },
  filterText:{
    color: '#ffffff'
  },
  imgCard:{
    width: width,
    height: 150,
    resizeMode: 'contain'
  },
});
