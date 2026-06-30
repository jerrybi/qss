import React, {Component} from "react";
import {
    Dimensions,
    NativeEventEmitter, NativeModules,
    PixelRatio,
    processColor,
    StatusBar,
    StyleSheet,
    Text, TouchableOpacity,
    View,
    Animated
} from "react-native";
import Global from "../utils/Global";
import {BarChart} from 'react-native-gifted-charts'
import {connectToDatabase} from "../../db/db";
import {getContacts, getContactsNum, getReportByFlag} from "../../db/contacts";
import singleCountEmitter from "../event/CountEmitter";
import BaseComponent from "./BaseComponent";

const {width,height} = Dimensions.get('window');

export default class HomeScreen extends BaseComponent {
    constructor(props) {
        super(props);
        this.state = {
            total:0,
            data:[]
        };
    }

    componentDidMount() {
        console.log('[home][componentDidMount]');
        singleCountEmitter.addListener('contacts_update',event=>{
            this.fetchContacts()
        })
        const av = new Animated.Value(0);
        av.addListener(() => {return});
        this.fetchContacts()
    }

    componentWillUnmount() {
        console.log('[home][componentWillUnmount]');
        singleCountEmitter.removeAllListeners('contacts_update')
    }

    fetchContacts(){
        connectToDatabase().then(db => {
            getReportByFlag(db).then(r => {
                console.log('[HomeScreen]fetchContacts')
                console.log(r)
                let names = ['Neutral','Cool','Warm','Hot']
                let values = []
                let data = []
                let unknownCount = this.getFlagCount(r,'Neutral');
                console.log('unknown:',unknownCount)
                if(unknownCount > 0){
                    this.addFlagItem(values,data,'Neutral',r,4);
                    this.addFlagItem(values,data,'Cool',r,4);
                    this.addFlagItem(values,data,'Warm',r,4);
                    this.addFlagItem(values,data,'Hot',r,4);
                }else{
                    this.addFlagItem(values,data,'Cool',r,3);
                    this.addFlagItem(values,data,'Warm',r,3);
                    this.addFlagItem(values,data,'Hot',r,3);
                }
                console.log(data)
                this.setState({
                    data: data
                })
            })
            getContactsNum(db).then(r=>{
                this.setState({
                    total:r
                })
            })
        })
    }

    getFlagCount(list,flag){
        for(let i = 0; i < list.length; i++){
            if(list[i].flag === flag){
                return list[i].count;
            }
        }
        return 0;
    }

    addFlagItem(values,data,flag,list,listCount){
        let count = this.getFlagCount(list,flag);
        values.push(count);
        data.push({
            value:count,
            label:flag,
            frontColor:flag === 'Cool'?'#0098e8':(flag === 'Warm'?'#ffb621':(flag === 'Hot'?'#ff0000':'lightgray')),
            topLabelComponent:()=>(<Text style={{color:'black', fontSize:16,marginBottom:6}}>{count}</Text>),
            spacing: listCount > 0 ? (width*0.8/listCount)-22 : (width*0.8/4)-22
        });
    }

    handleBackPress = () => {
        console.log('handleBackPress');
        console.log(this.props.navigation.state.routes);
        if(this.props.navigation.state.routes.length > 1){
            this.props.navigation.goBack();
            return true;
        }
        return false;
    }

    render() {
        return (
            <View style={styles.container}>
                <Text style={styles.analysis}>Analysis</Text>
                <Text style={styles.totalLabel}>TOTAL RECORDS SCANNED</Text>
                <Text style={styles.total}>{this.state.total}</Text>
                <BarChart
                    key={'xyz'}
                    isAnimated={true}
                    // animateOnDataChange
                    animationDuration={1000}
                    onDataChangeAnimationDuration={300}
                    width={width*0.8}
                    style={styles.chart}
                    barWidth={22}
                    noOfSections={5}
                    barBorderRadius={4}
                    frontColor="lightgray"
                    backgroundColor="#fecf9e"
                    data={this.state.data}
                    yAxisThickness={0}
                    yAxisTextStyle={{
                        color:'#000'
                        }}
                    xAxisThickness={0}
                    xAxisLabelTextStyle={{
                        color:'#000'
                    }}
                    onPress={(item, index) => this.clickBarItem(item)}
                    />
                <TouchableOpacity onPress={()=>{this.clickManualEntry()}}>
                    <Text style={styles.manualEntry}>Manual Entry</Text>
                </TouchableOpacity>

            </View>
        );
    }

    clickManualEntry() {
        this.props.navigation.navigate('ManualEntry');
    }

    clickBarItem(item){
        let label = item.label;
        this.props.navigation.navigate('Sync',{flag: label})
        singleCountEmitter.emit('flag_update',{flag: label});
    }
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        flexDirection: 'column',
        justifyContent: 'space-around',
        alignItems: 'center',
        backgroundColor: '#ffffff'
    },
    divider: {
        width: width,
        height: 1 / PixelRatio.get(),
        backgroundColor: Global.dividerColor
    },
    chart: {
        width:'80%',
        height:'50%'
    },
    analysis:{
        borderColor:'#999',
        borderWidth:1,
        color:'#000',
        fontSize:18,
        paddingLeft:10,
        paddingRight:10
    },
    totalLabel:{
        color:'#000',
        fontSize:20,
    },
    total:{
        color:'#000',
        fontSize:24,
    },
    manualEntry:{
        color:'#000',
        fontSize:16,
        textDecorationLine:'underline',
        textDecorationStyle:'solid',
        textDecorationColor:'#000'
    }
});
