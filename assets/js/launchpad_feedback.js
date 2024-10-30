(function ($){
    $(document).ready(function (){
        function resetForms(){
            $("#af-response").val('Article was helpful');
            $("#af-feedback,#af-name,#af-email").val('');
        }
        
        function submitForm(){
            // if(jQuery("#af-email").val()=="") {
            //     jQuery("#af-feedback-form .af-error").text("Please enter your email address.");
            //     return false;
            // } else {
            //     var email = jQuery('#af-email').val();
            //     if(email.indexOf("@") == -1 || email.indexOf(".") == -1) {
            //         jQuery("#af-feedback-form .af-error").text("Please enter a valid email address.");
            //         return false;
            //     } else {
            //         var message  = jQuery('#feedbackmessage').val();
            //         var name     = jQuery('#af-name').val();
            //         var feedback = jQuery('#af-feedback').val();
            //         var post_id  = jQuery('#af-post-id').val();
            //         var response = jQuery('#af-response').val();
            //         var data = {
            //             action: 'launchpad_feedback',
            //             name:name,
            //             email: email,
            //             feedback:feedback,
            //             post_id:post_id,
            //             response:response
            //         };
            //         //jQuery("#mailinglistsubmit").hide();
            //         //jQuery(".ajaxsave").show();
            //         jQuery.post(LaunchpadFeedback.ajaxurl, data,function(response){
            //             if(response=='success'){
            //                 //jQuery("#af-feedback-form").hide();
            //                 //jQuery('.thanks').removeClass('feedback-nodisplayall');
            //                 //jQuery(".thanks").addClass('feedback-displayall');
            //             } else{
            //                 jQuery("#af-feedback-form .af-error").html(response);
            //             }
            //         });     
            //         return false;
            //     }
            // } 
            var email    = jQuery('#af-email').val();
            var name     = jQuery('#af-name').val();
            var feedback = jQuery('#af-feedback').val();
            var post_id  = jQuery('#af-post-id').val();
            var response = jQuery('#af-response').val();
            var data = {
                action: 'launchpad_feedback',
                name:name,
                email: email,
                feedback:feedback,
                post_id:post_id,
                response:response
            };    
            // $.ajax({
            //    url: LaunchpadFeedback.ajaxurl,
            //    type: 'post',
            //    data: data
            // });
            jQuery.post(LaunchpadFeedback.ajaxurl, data,function(response){
                console.log(response);
            });  


            
            $('#af-yes,#af-no,#af-was').addClass('inactive');
        }
    

        $('.af-response-select').click(function(e){
            e.preventDefault();
            openPopup('#af-popup-2');
            $('#af-response').val($(this).text());
            
            $('.af-incorrect,.af-missing').css('display','none');
            $($(this).attr('data-show')).css('display','inline');
            
        });
        
        function closePopups(){
            $('#af-popup-1,#af-popup-2,#af-popup-3').css('display', 'none');
        }
        
        function openPopup(popupSelector){
            $('#af-popup-1,#af-popup-2,#af-popup-3').css('display', 'none');
            $(popupSelector).css('display','block');
        }

        $('#af-yes').click(function (e){
            e.preventDefault();
            if($(this).hasClass('inactive'))
            {
                return true;
            }
            resetForms();
            openPopup('#af-popup-3');
            $('#af-yes,#af-no,#af-was').addClass('inactive');
            /*submitForm();*/
        });
        
        $('.af-popup-content').click(function(e){
            e.stopPropagation();
        });
        
        $('#af-feedback-form').submit(function(e){
            e.preventDefault();
            openPopup('#af-popup-3');
            submitForm();
        });            
        
        $('#af-popup-1,#af-popup-2,#af-popup-3').click(function(){
            closePopups();
        });
            
        $('.af-close-popup').click(function(e){
            e.preventDefault();
            closePopups();
        });
            

        $('#af-no').click(function (e){
            e.preventDefault();
            if($(this).hasClass('inactive'))
            {
                return true;
            }
            resetForms();
            openPopup('#af-popup-1');
        });

        // jQuery(".m-feedback-prompt__social_thumbsup").on("click",function(e){
        //     e.preventDefault();
        //     jQuery(this).siblings('.m-feedback-prompt_form').removeClass('m-feedback-prompt__button--active');
        //     jQuery(this).toggleClass('m-feedback-prompt__button--active');
        //     jQuery(this).siblings('.m-feedback-prompt__form').removeClass('show');
        //     jQuery(this).siblings('.m-feedback-prompt__social').toggleClass("show")
            
        // });
        // jQuery(".m-feedback-prompt_form").on("click",function(e){
        //     e.preventDefault();
            
        //     jQuery(this).siblings('.m-feedback-prompt__social').removeClass('m-feedback-prompt__button--active');
        //     jQuery(this).toggleClass('m-feedback-prompt__button--active');
        //     jQuery(this).siblings('.m-feedback-prompt__social').removeClass('show');
        //     jQuery(this).siblings('.m-feedback-prompt__form').toggleClass("show");
        //     jQuery(this).siblings('.m-feedback-prompt__form').find("#af-feedback-form").show();
        //     jQuery(this).siblings('.m-feedback-prompt__form').find('.thanks').removeClass('feedback-displayall').addClass('feedback-nodisplayall');
        // });
    });    
})(jQuery);