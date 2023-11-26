<?php
require PROJECT_ROOT_PATH . "/models/ArtistModel.php";
class ArtistController extends BaseController
{
    /**
     * "/artist/get" Endpoint - Get artist from id
     */
    public function get_action() : void 
    {
        $this->require_request_method("GET");
        $this->require_params("id");

        $params = $this->get_query_string_params();
        $id = $params["id"];

        try {
            $model = new Artist();
            $artist_result = $model->get($id);
            $artist_result->object->countries = $model->get_countries_for($id);
            $this->output_ok($artist_result);
        } catch (Error $e) {
            $this->output_error_500(ObjectResult::from_error($e));
        }
    }
    
    /**
     * "/artist/get_list" Endpoint - Get list of all artists
     */
    public function get_list_action() : void
    {
        try {
            $model = new Artist();
            $result = $model->get_all();
            $this->output_ok($result);
        } catch (Error $e) {
            $this->output_error_500(DataResult::from_error($e));
        }
    }

    /**
     * "/artist/save" Endpoint - Save artist
     */
    public function save_action() : void 
    {
        $this->require_request_method("GET");

        $params = $this->get_query_string_params();

        try {
            $model = new Artist();
            $result = $model->save($params);
            $this->output_ok($result);
        } catch (Error $e) {
            $this->output_error_500(Result::from_error($e));
        }
    }
}