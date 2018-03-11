/*
 * *
 *  Copyright © 2016 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 *  
 */

define([
    'jquery'
], function($) {
    /*manage insert variables*/
    window.oscAddress = {
        init: function(options) {
            if(options){
                this.options = options;
            }
            if(options.oneFields){
                this.oneFields = options.oneFields;
            }
            if(options.twoFields){
                this.twoFields = options.twoFields;
            }
            if(options.lastFields){
                this.lastFields = options.lastFields;
            }
        },
        fieldAfterRender: function(fieldName){
            if($(".field[name='"+fieldName+"']").length > 0){
                if(this.isTwoField(fieldName)){
                    $(".field[name='"+fieldName+"']").addClass('two-fields');
                }else{
                    //if(this.isOneField(fieldName)){
                        $(".field[name='"+fieldName+"']").addClass('one-field');
                    //}
                }
                if(this.isLastField(fieldName)){
                    $(".field[name='"+fieldName+"']").addClass('last');
                }
            }
        },
        isOneField: function(fieldName){
            if(this.oneFields){
                var found = false;
                $.each(this.oneFields, function(){
                    if(fieldName.match('.'+this+'$')){
                        found = true;
                        return true;
                    }
                })
                return found;
            }
            return false;
        },
        isTwoField: function(fieldName){
            if(this.twoFields){
                var found = false;
                $.each(this.twoFields, function(){
                    if(fieldName.match('.'+this+'$')){
                        found = true;
                        return true;
                    }
                })
                return found;
            }
            return false;
        },
        isLastField: function(fieldName){
            if(this.lastFields){
                var found = false;
                $.each(this.lastFields, function(){
                    if(fieldName.match('.'+this+'$')){
                        found = true;
                        return true;
                    }
                })
                return found;
            }
            return false;
        }
    };

    return window.oscAddress;
});