<?php
class FollowUpPlugin extends MantisPlugin
{
    function register()
    {
        $this->name = 'Follow Up';                                # Proper name of plugin
        $this->description = 'Mark notes containing useful help'; # Short description of the plugin
        $this->page = '';                                         # Default plugin page
        $this->version = '1.0';                                   # Plugin version string
        $this->requires = array(                                  # Plugin dependencies, array of basename => version pairs
            'MantisCore' => '1.2.0',                              # Should always depend on an appropriate version of MantisBT
        );
        $this->author = 'Eyal Azulay';                            # Author/team name
        $this->contact = 'eyal@get-it-write.com';                 # Author/team e-mail address
        $this->url = 'https://www.get-it-write.com/';             # Support webpage
    }

    function hooks()
    {
        return array(
            'EVENT_MENU_FILTER' => 'follow_up_menu',
        );
    }

    function follow_up_menu()
    {
        $t_has_develper_access_level = access_has_project_level(DEVELOPER);
        if ($t_has_develper_access_level) {
            return array(
                '<a href="' . plugin_page('view') . '">Follow Up</a>'
            );
        }
        return array();
    }
}
