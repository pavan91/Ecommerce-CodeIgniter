<?php
/* 
 * Generated by CRUDigniter v3.2 
 * www.crudigniter.com
 */
 
class Product extends CI_Controller{
    function __construct()
    {
        parent::__construct();
		IsLogin();
        $this->load->model('admin/Product_model');
		$this->load->model('admin/Productcat_model');
		$this->load->model('admin/Productgallery_model');
		
    } 

    /*
     * Listing of product
     */
    function index()
    {
		
        $this->load->library('pagination');
		$params['limit'] = RECORDS_PER_PAGE; 
        $params['offset'] = ($this->input->get('per_page')) ? $this->input->get('per_page') : 0;

		$params["filter"]=$this->input->get("filter");
		$params["search"]=$this->input->get("search");
		$config['base_url'] = base_url()."/index.php/admin/product/index?";
		$config['total_rows'] = $this->Product_model->get_all_product_count2($params["filter"]);		
		

		$config['per_page']=RECORDS_PER_PAGE;
		
		if($params["search"])
		{
			$config["total_rows"]=1000;
			$config["per_page"]=1000;
		}
		
		$config['page_query_string'] = TRUE;
		
        $this->pagination->initialize($config);
		$data["sr"]=($this->input->get('per_page')) ? $this->input->get('per_page') : 0;
		$data["sr"]=$data["sr"]+1;
        $data['product'] = $this->Product_model->get_all_product($params);
        
        $data['_view'] = 'admin/product/index';
        $this->load->view('admin/layouts/main',$data);
    }
	
	function expired_deals()
	{
		 $data['products'] = $this->Product_model->get_all_expired_product();
        
        $data['_view'] = 'admin/product/expired_deals';
        $this->load->view('admin/layouts/main',$data);
	}
	
	
	function reschedule()
	{
		
		$this->Product_model->reschedule();
		redirect("admin/Product/Index");
	}

    /*
     * Adding a new product
     */
    function add()
    {   
        if(isset($_POST) && count($_POST) > 0)     
        {   
			
	
            $params = array(
				'ProductName' => $this->input->post('ProductName'),
				'Price' => $this->input->post('Price'),
				'SalePrice' => $this->input->post('SalePrice'),
				'SaleStartDate' => $this->input->post('SaleStartDate'),
				'SaleEndDate' => $this->input->post('SaleEndDate'),
				'ProductCatId' => $this->input->post('ProductCatId')	,
				'LongDescription' => $this->input->post('LongDescription'),
				'productVariation' => $this->input->post('productVariation'),
				'IsDraft'=> $this->input->post('IsDraft'),
				'isnewthankpage'=> $this->input->post('isnewthankpage'),
				'IsDirectAccess'=> $this->input->post('IsDirectAccess')
            );
            
            $product_id = $this->Product_model->add_product($params);
			
			 if (!empty($_FILES)) {
				
				if(!empty($_FILES["Featured"]))
				{
					
					
							$this->load->library('upload');      
							$config['upload_path'] = "uploads/";      
							$config['allowed_types'] = 'jpg|png';
							
							$this->upload->initialize($config);
							
							if ($this->upload->do_upload('Featured'))
							{
								$featureImage = $this->upload->data();
								$singleImage = array("ImagePath"=>$featureImage["file_name"],"ProductId"=>$product_id,"IsFeatured"=>1,"IsDelete"=>0);
								
								$this->db->insert("productgallery",$singleImage);								
								
								create_thumbnail($featureImage["full_path"]);
							}
							
					
				}
				if(!empty($_FILES["ProductImages"]))
				{
					    $number_of_files = sizeof($_FILES['ProductImages']['tmp_name']);
						$files = $_FILES['ProductImages'];
						$errors = array();
						$data=array();
						
						
						for($i=0;$i<$number_of_files;$i++)
						{
							if($_FILES['ProductImages']['error'][$i] != 0) $errors[$i][] = 'Couldn\'t upload file '.$_FILES['ProductImages']['name'][$i];
						}
						
						if(sizeof($errors)==0)
						{
      
							
							$this->load->library('upload');
      
							$config['upload_path'] = "uploads/";      
							$config['allowed_types'] = 'jpg|png';
      
							for ($i = 0; $i < $number_of_files; $i++) {
								$_FILES['ProductImages']['name'] = $files['name'][$i];
								$_FILES['ProductImages']['type'] = $files['type'][$i];
								$_FILES['ProductImages']['tmp_name'] = $files['tmp_name'][$i];
								$_FILES['ProductImages']['error'] = $files['error'][$i];
								$_FILES['ProductImages']['size'] = $files['size'][$i];
        							
							$this->upload->initialize($config);
        
							if ($this->upload->do_upload('ProductImages'))
							{
								$data['uploads'][$i] = $this->upload->data();
								$singleImage = array("ImagePath"=>$data['uploads'][$i]["file_name"],"ProductId"=>$product_id,"IsFeatured"=>0,"IsDelete"=>0);
								$this->db->insert("productgallery",$singleImage);
								
								create_thumbnail($data["uploads"][$i]["full_path"]);
							}
							else
							{
								$data['upload_errors'][$i] = $this->upload->display_errors();
							}
							}							
						}						
				}
				
				
			}
			
			
            //redirect('admin/product/index');
			$data["ProductCategoryArray"]=$this->Productcat_model->get_all_productcat_dropdown();
			
			//$data['Product']=(object)$product;
			
			redirect('admin/product/edit/'.$product_id);
        }
        else
        {            
			
            $data["ProductCategoryArray"]=$this->Productcat_model->get_all_productcat_dropdown();
			$data['_view'] = 'admin/product/add';
            $this->load->view('admin/layouts/main',$data);
        }
    }  

	function duplicate($ProductId)
	{
		$duplicateProductId = $this->Product_model->duplicate_product($ProductId);
		
		redirect('admin/product/edit/'.$duplicateProductId);
	}
	
	
    /*
     * Editing a product
     */
    function edit($ProductId)
    {   
        // check if the product exists before trying to edit it
        $data['product'] = $this->Product_model->get_product($ProductId);
        
        if(isset($data['product']['ProductId']))
        {
            if(isset($_POST) && count($_POST) > 0)     
            {   
                $params = array(
					'ProductName' => $this->input->post('ProductName'),
					'Price' => $this->input->post('Price'),
					'SalePrice' => $this->input->post('SalePrice'),
					'SaleStartDate' => $this->input->post('SaleStartDate'),
					'SaleEndDate' => $this->input->post('SaleEndDate'),
					'ProductCatId' => $this->input->post('ProductCatId'),
					'LongDescription' => $this->input->post('LongDescription'),
					'productVariation' => $this->input->post('productVariation'),
					'IsStockAvailable'=> $this->input->post('IsStockAvailable'),
					'IsDraft'=> $this->input->post('IsDraft'),
					'isnewthankpage'=> $this->input->post('isnewthankpage'),
					'IsDirectAccess'=> $this->input->post('IsDirectAccess')					
                );

				
                $this->Product_model->update_product($ProductId,$params);            
				
				$IdsToRemove = $this->input->post("galleryIdsToRemove");
				if(!empty($IdsToRemove))
				{
					$IdsToRemove = ltrim($IdsToRemove,",");
					$IdsListToRemoveArray = explode(",",$IdsToRemove);
					
					foreach($IdsListToRemoveArray as $galleryId)
					{
						$this->Productgallery_model->delete_productgallery($galleryId);
					}
				}
				
				if(!empty($_FILES))
				{
					if(!empty($_FILES["Featured"]))
					{
							$this->load->library('upload');      
							$config['upload_path'] = "uploads/";      
							$config['allowed_types'] = 'jpg|png';
							$this->upload->initialize($config);
							
							if ($this->upload->do_upload('Featured'))
							{
								$featureImage = $this->upload->data();
								$singleImage = array("ImagePath"=>$featureImage["file_name"],"ProductId"=>$ProductId,"IsFeatured"=>1,"IsDelete"=>0);
								$this->Productgallery_model->update_featuredImage($ProductId,$singleImage);
								create_thumbnail($featureImage["full_path"]);
							}
					}
					
					if(!empty($_FILES["ProductImages"]))
					{
						$number_of_files = sizeof($_FILES['ProductImages']['tmp_name']);
						$files = $_FILES['ProductImages'];
						$errors = array();
						$data=array();
						
						for($i=0;$i<$number_of_files;$i++)
						{
							if($_FILES['ProductImages']['error'][$i] != 0) $errors[$i][] = 'Couldn\'t upload file '.$_FILES['ProductImages']['name'][$i];
						}
						
						if(sizeof($errors)==0)
						{
							$this->load->library('upload');
      
							$config['upload_path'] = "uploads/";      
							$config['allowed_types'] = 'jpg|png';
      
							for ($i = 0; $i < $number_of_files; $i++) {
								$_FILES['ProductImages']['name'] = $files['name'][$i];
								$_FILES['ProductImages']['type'] = $files['type'][$i];
								$_FILES['ProductImages']['tmp_name'] = $files['tmp_name'][$i];
								$_FILES['ProductImages']['error'] = $files['error'][$i];
								$_FILES['ProductImages']['size'] = $files['size'][$i];
        							
							$this->upload->initialize($config);
        
							if ($this->upload->do_upload('ProductImages'))
							{
								$data['uploads'][$i] = $this->upload->data();
								$singleImage = array("ImagePath"=>$data['uploads'][$i]["file_name"],"ProductId"=>$ProductId,"IsFeatured"=>0,"IsDelete"=>0);
								$this->Productgallery_model->add_productgallery($singleImage);
								
								create_thumbnail($data["uploads"][$i]["full_path"]);
							}
							else
							{
								$data['upload_errors'][$i] = $this->upload->display_errors();
							}
							}							
						}
						
					}
				}
				
                redirect('admin/product/edit/'.$ProductId);
            }
            else
            {
				$data["ProductCategoryArray"]=$this->Productcat_model->get_all_productcat_dropdown();
				$data["FeaturedImage"]=$this->Productgallery_model->get_product_featureimage($ProductId);
				$data["ProductImages"]=$this->Productgallery_model->get_productimages($ProductId);
				$product = array("ProductId"=>$ProductId,"Slug"=>slugify($data["product"]["ProductName"]));
				$data["Product"]=(object)$product;
                $data['_view'] = 'admin/product/edit';
                $this->load->view('admin/layouts/main',$data);
            }
        }
        else
            show_error('The product you are trying to edit does not exist.');
    } 

    /*
     * Deleting product
     */
    function remove($ProductId)
    {
        $product = $this->Product_model->get_product($ProductId);

        // check if the product exists before trying to delete it
        if(isset($product['ProductId']))
        {
            $this->Product_model->delete_product($ProductId);
            redirect('admin/product/index');
        }
        else
            show_error('The product you are trying to delete does not exist.');
    }
    
}
