<?php
use RavidTools\CommonFunctions;

defined('C5_EXECUTE') or die('Access Denied.');

echo "<i id=instructionsTrigger class='form-label launch-tooltip fa fa-question-circle'></i>";
echo "<div id=instructions  style='display:none;' class=row>";
        echo '<h3>' . t('Instructions') . '</h3>';
        echo '<p>' . t("This block is used to create destination content for landing pages from trigger links. Trigger links are those as created in high level.  To use the block<OL>
                            <LI>Create the trigger link in high-level.  Point the URL to the destination page on your website that contains this block and add all the dynmic content needed which will be used on this page
                                <br><small>ex: https://ravidenterprises.com/trigger-link-destinations/thank-you-registering?email={{contact.email}}&first_name={{contact.first_name}}&appttime=02-20-2026 03:00 PM&subject=Developing Your Online Presence Webinar</small>
                            <LI>Copy/Paste the link url text from high level into the box on this page.  That will populate the field list
                            <LI>Write your content and insert the fields where needed
                            </OL>
                             ") . '</p>';
echo "</div>";
echo "<div class='row col'>";
    echo CommonFunctions::formHelper('triggerlink',"Trigger Link URL",$triggerlink);
echo "</div>";
echo "<div class=row>";
    echo '<div class="col-8">';
        echo CommonFunctions::formHelper('content', 'Text to Display',$content, 'richtextarea');
    echo '</div>';
    
    echo "<div id=linkFields class='col-4'>";
        
    echo '</div>';
echo '</div>';

?>
<SCRIPT>
    var formFieldPrototype=`<div class='input-group mb-2'>
        <div class='input-group-prepend'>
          <div class='input-group-text  copyclip'><i class='fa fa-clone' aria-hidden='true'></i></div>
        </div>
        <input type='text' class='form-control' value='{{field}}'>
      </div>`;
    console.log('myscript');
    
    function updateFieldsFromTriggerLink(){
        $('#linkFields').html("");
        var text=$('#triggerlink').val();
        fields=text.matchAll(/[&?]([^=&]+)=([^&]*)/gm).toArray();
        if(fields==null){
            alert("No fields defined in string");
        }else{
            for(const field of fields) {
                $('#linkFields').append(formFieldPrototype.replace('{{field}}','{{'+field[1]+'}}'));
            }
        }        

        //rebind
        $('.copyclip').click(function(){
            navigator.clipboard.writeText($(this).next('input').val());
        });
    }

    
    $(function(){	
        $('#triggerlink').change(function(){
            updateFieldsFromTriggerLink();
        })

        $('#instructionsTrigger ').click(function(){$('#instructions').toggle();})    

        if($('#triggerlink').val().length>0) updateFieldsFromTriggerLink();
    });

</SCRIPT>