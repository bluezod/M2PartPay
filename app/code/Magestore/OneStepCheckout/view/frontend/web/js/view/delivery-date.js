/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */
/*global define*/
define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'mage/calendar'
    ],
    function($, ko, Component) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Magestore_OneStepCheckout/delivery-date'
            },

            isShowDelivery: ko.observable(window.checkoutConfig.delivery_time_date),

            isShowSecurityCode: ko.observable(window.checkoutConfig.is_enable_security_code),

            currentDate: function () {
                var currentDate = new Date();
                var year = currentDate.getFullYear();
                var month = currentDate.getMonth() + 1;
                var date = currentDate.getDate();
                var day = currentDate.getDay();

                var disableDay = window.checkoutConfig.disable_day;
                if (disableDay) {
                    var disableDayArray = disableDay.split(',').map(Number);
                    var i;

                    for (i=0; i<=6; i++) {
                        if ($.inArray(day, disableDayArray) == -1 ) {
                            year = currentDate.getFullYear();
                            month = currentDate.getMonth() + 1;
                            date = currentDate.getDate();
                            return month + '/' + date + '/' + year;
                        }
                        currentDate.setDate(currentDate.getDate() + 1)
                        day = currentDate.getDay();
                    }
                }
                return month + '/' + date + '/' + year;
            },

            listTime: function() {
                var listTime = window.checkoutConfig.delivery_hour;
                if(listTime != ""){
                    var listTimeArray = listTime.split(',');
                    var newTimeArray = [];
                    $.each(listTimeArray, function (index, value) {
                        newTimeArray[index] = value + ':00';
                    });
                    return newTimeArray;
                }
                return false;
            },

            initDate: function () {
                var currentDate = new Date();
                var year = currentDate.getFullYear();
                var month = currentDate.getMonth();
                var day = currentDate.getDate();
                var self = this;
                $("#delivery_date").calendar({
                    showsTime: false,
                    controlType: 'select',
                    timeFormat: 'HH:mm TT',
                    showTime: false,
                    minDate: new Date(year, month, day, '00', '00', '00', '00'),
                    beforeShowDay: self.disableDate
                });
            },

            disableDate: function (date) {
                var day = date.getDay();
                var disableDay = window.checkoutConfig.disable_day;
                if (disableDay) {
                    var disableDayArray = disableDay.split(',').map(Number);
                    // Now check if the current date is in disabled dates array.

                    if ($.inArray(day, disableDayArray) != -1 ) {
                        return [false];
                    } else {
                        return [true];
                    }
                } else {
                    return [true];
                }

            }
        });
    }
);
