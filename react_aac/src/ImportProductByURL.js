import {
  Form, Tag, ProgressBar, FormLayout, MediaCard, TextField, Button, Page, Card, Link, Spinner, LegacyCard, Tabs, IndexTable, DataTable, Thumbnail, Frame, Modal, TextContainer, InlineStack
} from '@shopify/polaris';
import axios from 'axios';
import React, { useState, useCallback, useEffect } from 'react';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import wrap from 'word-wrap';
import './Styles.css';

export default function ImportProductByURL() {
  const [isLoading, setIsLoading] = useState(false);

  const [data, setData] = useState('');
  const [data1, setData1] = useState('');
  const [products, setProducts] = useState('');
  const [token, setToken] = useState('');
  const [producturl, setProductUrl] = useState('');

  const [selected, setSelected] = useState(0);
  const [urlError, setUrlError] = useState('');
  const [userPlan, setUserPlan] = useState('');
  const [userPlanTest, setUserPlanTest] = useState('');
  const [fetchNewData, setFetchNewData] = useState("");

  const [productData, setProductData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const [responseData2, setResponseData2] = useState(null); // State to store response data
  const [responseData, setResponseData] = useState(null); // State to store response data
  const [showImportedProduct, setShowImportedProduct] = useState(false);

  const [progress, setProgress] = useState(0);
  const [intervalId, setIntervalId] = useState(null);

  const [active, setActive] = useState(false);

  const handleChange = useCallback(() => setActive(!active), [active]);

  const { product_id, title, status, variants } = fetchNewData;

  const tempcode = localStorage.getItem('tempcode');
  const initialValues = {
    key: tempcode,
  };
  useEffect(() => {
    const getShopifyThemeId = async () => {
      try {
        const response = await axios.post(`${process.env.REACT_APP_BASE_URL}/authenticate`, initialValues);
        setData(response.data);
        setToken(response.data.token);
        setUserPlan(response.data.user.plan);
      } catch (error) {
        console.error('Error fetching token:', error);
      }
    };

    getShopifyThemeId();
  }, []);

  const handleUrlChange = useCallback((value) => {
    setProductUrl(value);
    // Resetting the error when user changes the URL
    setUrlError('');
  }, []);

  const handleBothActions = async (e) => {
    // e.preventDefault(); // Prevent default form submission behavior

    setIsLoading(true); // Start loading

    try {
      // Call previewbutton function
      await previewbutton(e);

      // Call handleSubmit function
      await handleSubmit();

      setIsLoading(false); // Stop loading if both functions succeed
    } catch (error) {
      setIsLoading(false); // Ensure loading state is reset in case of an error
      console.error('Error:', error);
    }
  };

  // Existing previewbutton function (updated to remove loading state changes)
  const previewbutton = async (e) => {
    // e.preventDefault(); // Commented out because it is handled in handleBothActions
    try {
      const startTime = Date.now(); // Record start time
      const response = await axios.post(`${process.env.REACT_APP_BASE_URL}/submit-form?lang=en-us`,
        { text_value: producturl },
        {
          headers: {
            Authorization: `Bearer ${token}` // Use token from response data
          }
        }
      );
      const endTime = Date.now(); // Record end time
      setResponseData2(response.data.response); // Set response data in state
      setActive(true);
    } catch (error) {
      console.error(error);
    }
  };

  // Existing handleSubmit function (updated to remove loading state changes)
  const handleSubmit = async () => {
    let id;
    try {
      // Start the progress increment
      id = setInterval(() => {
        setProgress(prev => (prev < 95 ? prev + 5 : prev)); // Increment progress but keep it under 95
      }, 500); // Adjust the interval duration as needed
      setIntervalId(id);

      const response = await axios.post(`${process.env.REACT_APP_BASE_URL}/product/add?lang=en-us`,
        { producturl },
        {
          headers: {
            'Authorization': `Bearer ${token}`,
          },
        });
      const result = response.data[0];
      setFetchNewData(result);
      toast.success("Product added successfully");
      setActive(false);
      setShowImportedProduct(true);      
      setProductUrl('');
      setUserPlanTest('If you want to add products with multiple variants please upgrade the plan.');
      // Set progress to 100 on success
      setProgress(100);
      // setTimeout(() => {
      //   setShowImportedProduct(false);
      // }, 300000); 
    } catch (error) {
      console.error('Error:', error);

      // Clear the interval on error
      clearInterval(id);

      // Extract and print the error message from the response
      const errorMsg = error.response?.data?.error?.msg ? error.response.data.error.msg[0] : 'An error occurred';
      console.error('Error message:', errorMsg);

      // Display the error message as a toast notification
      toast.error(`Error: ${errorMsg}`);

      // Set progress to 0 on error
      setProgress(0);
    } finally {
      // Clear the interval when the request completes or fails
      clearInterval(id);
      setIntervalId(null);
      setTimeout(() => setProgress(0), 1000); // Reset progress bar after a short delay
    }
  };

  const handleValidation = () => {
    if (!producturl) {
      setUrlError('URL is required');
      return false;
    }
    // Add additional validation logic here if needed
    return true;
  };


  const resourceName = {
    singular: "product",
    plural: "products",
  };

  const renderTabContent = () => {
    switch (selected) {
      case 0:
        return (
          <>
            <LegacyCard.Section title="">
              <Form onSubmit={() => {
                if (handleValidation()) {
                  handleBothActions();
                  // handleSubmit();
                }
              }}>
                <FormLayout>
                  <div className="producturl-container">
                    <div className='producturl'>

                      <TextField
                        value={producturl}
                        onChange={handleUrlChange}
                        placeholder="Please enter Amazon product URL"
                        type="text"
                        autoComplete="off"
                        error={urlError}
                      />
                    </div>

                    <div className='producturl-submit-button'><Button submit loading={isLoading}>Submit</Button></div>
                  </div>

                  {userPlan == 0 ? (
                    <span style={{ color: 'red', fontWeight: 'bold', fontSize: 'large' }}>{userPlanTest}</span>
                  ) : (
                    <></>
                  )}

                </FormLayout>
              </Form>
            </LegacyCard.Section>
          </>
        );
      default:
        return null;
    }
  };
 

  useEffect(() => {
    const timer = setTimeout(() => {
      setShowImportedProduct(true);
    }, 60000); // 60000 milliseconds = 1 minute

    return () => clearTimeout(timer); // Cleanup the timer if the component unmounts
  }, []);

  const getVariationsDescription = (variations) => {
    if (!Array.isArray(variations)) return ''; // Ensure variations is an array
    const groupedVariations = variations.reduce((acc, variation) => {
      if (!acc[variation.variationName]) {
        acc[variation.variationName] = new Set();
      }
      acc[variation.variationName].add(variation.variationValue);
      return acc;
    }, {});

    return Object.entries(groupedVariations).map(([name, values]) => (
      <>
        <div key={name}>
          <strong>{name}: </strong>
          {/* {Array.from(values).join(', ')}           */}
          <InlineStack>
            {Array.from(values).map((value, index) => (
              <Tag key={index} className="padded-tag">{value}</Tag>
            ))}
          </InlineStack>

        </div>
      </>
    ));
  };
  const variationdescription = getVariationsDescription(responseData2?.asinVariationValues);


  const description = getVariationsDescription(responseData2?.asinVariationValues);
  const featuresDescription = responseData2?.features?.map((feature, index) => (
    <div key={index}>{feature}</div>
  ));


  const aaaaa = 1;
  const importedproduct = () => {
    const wrappedTitle = wrap(title, { width: 50, indent: '' });
    return (
    <>
        <div className='preview-imported-products'>
          <div class="Polaris-Box">
            <h2 className="Polaris-Text--root Polaris-Text--headingLg">Imported product</h2>
          </div>         
        
        <React.Fragment key={product_id}>
          <IndexTable
            resourceName={resourceName}
            itemCount={aaaaa}					
            sortable={[false, true, true, true, true, true, true]}
            headings={[
              { title: "Image" },
              { title: "Title" },
              { title: "ASIN" },
              { title: "Variants" },
              { title: "Price" },
              { title: "Status" },
            ]}
          >
          <React.Fragment key={product_id}>
            <IndexTable.Row
            id={product_id}
            selected={false} // Adjust this according to your selected resources logic
            >
            {variants && Array.isArray(variants) && variants.map((variant, vIndex) => (
              <React.Fragment key={vIndex}>
              <IndexTable.Cell><img src={variant.main_image.imgurl} width="100" height="100" /></IndexTable.Cell>						
              </React.Fragment>
            ))}
            
            <IndexTable.Cell>
            {wrappedTitle ? (
              wrappedTitle.split('\n').map((line, idx) => (
                <span key={idx}>{line}<br /></span>
              ))
            ) : (
              <span></span> // Provide a fallback in case wrappedTitle is undefined
            )}
          </IndexTable.Cell>					
            {variants && Array.isArray(variants) && variants.map((variant, vIndex) => (
              <React.Fragment key={vIndex}>
              <IndexTable.Cell>{variant.asin}</IndexTable.Cell>
              <IndexTable.Cell>{variant.sku}</IndexTable.Cell>
              <IndexTable.Cell>{variant.price}</IndexTable.Cell>
              <IndexTable.Cell>{variant.status}</IndexTable.Cell>						
              </React.Fragment>
            ))}
            </IndexTable.Row>
          </React.Fragment>
          </IndexTable>
        </React.Fragment>    
      </div>
    </>
    );
  };
  return (
    <div className='importproductbyurl'>
      <Page title={"Import Product By URL"}
        secondaryActions={[
          {
            content: (
              <Link className="helplink" style={{ 'backgrounColor': '#1c2260' }} target="_blank" to="https://infoshoreapps.zendesk.com/hc/en-us/articles/360016229474-How-to-import-product-by-URL-to-your-Shopify-store">
                Need Help?
              </Link>
            ),
          },
        ]}
      >
        <ToastContainer />
        <Card>
          {renderTabContent()}
        </Card>
        
        {showImportedProduct ? <><div style={{ marginTop: '20px' }}>
          <Card>
            {importedproduct()}            
          </Card>
        </div></> : <></>} 
        

      </Page>

      <div style={{ height: '500px' }}>
        <Frame>
          <Modal
            open={active}
            onClose={handleChange}
            title="Import product preview"
          >
            <Modal.Section>
              <TextContainer>
                <Card>
                  <div style={{ width: 550 }}>
                  <ProgressBar progress={progress} color="success"/>
                  </div>
                </Card>
                {responseData2 && (
                  <MediaCard
                    title={responseData2.name}
                    description={<><div className='variation-list'>{description}</div><br></br><div className='short-description'><h5><strong>Short Description:</strong></h5> {featuresDescription}</div></>}
                  >
                    <img
                      alt=""
                      width="100%"
                      height="100%"
                      style={{
                        objectFit: 'cover',
                        objectPosition: 'center',
                      }}
                      src={responseData2.mainImage}
                    />
                  </MediaCard>
                )}
              </TextContainer>
            </Modal.Section>
          </Modal>
        </Frame>
      </div>

    </div>


  );
}
