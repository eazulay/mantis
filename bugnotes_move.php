<?php
    require_once( 'core.php' );
    require_once( 'bug_api.php' );
    require_once( 'bugnote_api.php' );
    require_once( 'current_user_api.php' );

    $t_bug_id = gpc_get_int( 'bug_id' );
    $t_user_id = auth_get_current_user_id();

    form_security_validate( 'bugnotes_move' );

    if (isset($_POST['move_to_bug_id']))
        $f_move_to_bug_id = gpc_get_int( 'move_to_bug_id' );
    if (isset($_POST['note_selected']))
        $selected_notes = $_POST['note_selected'];
    $record_move = gpc_get_bool( 'record_transfer' );

    if (isset($f_move_to_bug_id) && isset($selected_notes)){
        # Check if the bug is readonly
        if ( bug_is_readonly( $f_move_to_bug_id ) ) {
            error_parameters( $f_move_to_bug_id );
            trigger_error( ERROR_BUG_READ_ONLY_ACTION_DENIED, ERROR );
        }
        foreach($selected_notes as $f_bugnote_id => $checked){
            # Check if the current user is allowed to edit the bugnote
            $t_reporter_id = bugnote_get_field( $f_bugnote_id, 'reporter_id' );

            if ( ( $t_user_id != $t_reporter_id ) || ( OFF == config_get( 'bugnote_allow_user_edit_delete' ) )) {
                access_ensure_bugnote_level( config_get( 'update_bugnote_threshold' ), $f_bugnote_id );
            }

            if ($f_move_to_bug_id > 0)
                bugnote_set_bug_id( $f_bugnote_id, $f_move_to_bug_id, $record_move );
        }
    }

    form_security_purge( 'bugnotes_move' );
    print_successful_redirect(string_get_bug_view_url($t_bug_id) . '#bugnotes');