import React, { useState, useCallback, useEffect } from "react";
import {
  Form,
  FormLayout,
  Button,
  Page,
  Card,
  Select,
  Text,
  Spinner,
  Banner,
  Layout,
  LegacyCard,
  IndexTable,
  Badge,
  ColorPicker,
} from "@shopify/polaris";
import axios from "axios";
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

export default function BulkImport() {
  const [isLoading, setIsLoading] = useState(false);
  const [data, setData] = useState("");
  const [token, setToken] = useState("");
  const [bulkImports, setBulkImports] = useState("");
  const [selectedOption, setSelectedOption] = useState("www.amazon.com");
  const [selectedFile, setSelectedFile] = useState(null);
  const [error, setError] = useState('');

  const tempcode = localStorage.getItem('tempcode');
  const initialValues = {
    key: tempcode,
  };

  useEffect(() => {
    const getShopifyThemeId = async () => {
      try {
        const response = await axios.post(
          `${process.env.REACT_APP_BASE_URL}/authenticate`,
          initialValues
        );
        setData(response.data);
        setToken(response.data.token);
        console.log("response-token", response.data.token);
      } catch (error) {
        console.error("Error fetching token:", error);
      }
    };
    getShopifyThemeId();
  }, []); // Empty dependency array means this useEffect runs only once

  const handleSubmit = useCallback(
    (e) => {
      // Validation
      if (!selectedOption || !selectedFile) {
        setError('This field is required.');
        return;
      }
      setIsLoading(true);
      e.preventDefault();
      const reader = new FileReader();
      reader.readAsDataURL(selectedFile);
      reader.onload = () => {
        const base64File = reader.result;
        const formData = {
          url: selectedOption,
          file: base64File,
        };
        axios
          .post(`${process.env.REACT_APP_BASE_URL}/import?lang=en-us`, formData, {
            headers: {
              "Content-Type": "application/json",
              Authorization: `Bearer ${token}`,
            },
          })
          .then((response) => {
            // Handle success
            console.log(response.data);
            setSelectedOption("");
            setSelectedFile(null);
            setIsLoading(false);
          })
          .catch((error) => {
            setIsLoading(false);
            if (error.response.data.error.msg[0]) {
              toast.error(`Error: ${error.response.data.error.msg[0]}`); // Display the error message received from the server
            } else {
              toast.error("Something went wrong !!");
            }
            console.error('Error:', error);
          });
      };

      reader.onerror = (error) => {
        console.error("Error reading file:", error);
      };
    },
    [selectedOption, selectedFile, token]
  );

  const handleSelectChange = useCallback((value) => setSelectedOption(value), []);
  const handleFileChange = useCallback((event) => {
    setSelectedFile(event.target.files[0]);
  }, []);


  useEffect(() => {
    const fetchBulkImports = async () => {
      if (token) { // Check if token is available
        try {
          const response = await axios.get(`${process.env.REACT_APP_BASE_URL}/import?lang=en-us`, {
            headers: {
              'Authorization': `Bearer ${token}`,
            },
          });
          setBulkImports(response.data);
          // setProductlist(response.data.data);
        } catch (error) {
          console.error('Error fetching products:', error);
        }
      }
    };

    fetchBulkImports();
  }, [token]); // This useEffect will run whenever 'token' changes

  console.log('bulkImports', bulkImports);

  const resourceName = {
    singular: "Import product list",
    plural: "products",
  };
  const bulkitemCount = bulkImports.length;
  return (
    <Page title={"Bulk Import"}>
      <ToastContainer />
      <Card>
        <Form noValidate onSubmit={handleSubmit}>
          <FormLayout>
            <Select
              label="Please Enter Amazon's Base URL"
              options={[
                { label: "www.amazon.com", value: "www.amazon.com" },
                { label: "www.amazon.ca", value: "www.amazon.ca" },
                { label: "www.amazon.in", value: "www.amazon.in" },
                { label: "www.amazon.co.uk", value: "www.amazon.co.uk" },
                { label: "www.amazon.com.br", value: "www.amazon.com.br" },
                { label: "www.amazon.com.mx", value: "www.amazon.com.mx" },
                { label: "www.amazon.de", value: "www.amazon.de" },
                { label: "www.amazon.es", value: "www.amazon.es" },
                { label: "www.amazon.fr", value: "www.amazon.fr" },
                { label: "www.amazon.it", value: "www.amazon.it" },
                { label: "www.amazon.co.jp", value: "www.amazon.co.jp" },
                { label: "www.amazon.cn", value: "www.amazon.cn" },
                { label: "www.amazon.com.au", value: "www.amazon.com.au" },
              ]}
              value={selectedOption}
              onChange={handleSelectChange}
            />
            <Text>
              Please Choose ASIN CSV (Please upload ASIN in line separated
              format)
            </Text>
            <input type="file" accept=".csv,.xlsx,.xls" onChange={handleFileChange} />
            {error && <p style={{ color: 'red' }}>{error}</p>}
            <Button submit loading={isLoading} primary>Submit</Button>
          </FormLayout>
        </Form>
      </Card>
      <div style={{ height: '500px', paddingTop: '20px' }} className="bulkproductlist">
        <Card>
          <div className="Polaris-Box" style={{ paddingBottom: '20px' }}>
            <h2 className="Polaris-Text--root Polaris-Text--headingLg">Imported product</h2>
          </div>
          <IndexTable
            resourceName={resourceName}
            itemCount={bulkitemCount}
            sortable={[false, false, false, false]}
            showSelection={false}
            selectable={false}
            headings={[
              { title: "ASIN" },
              { title: "failed ASIN" },
              { title: "Status" },
              { title: "Result(failed/total)" },
              { title: "Created At" },
            ]}
          >
            {bulkImports && Array.isArray(bulkImports) && bulkImports.map((bulkImport, vIndex) => (
              <React.Fragment key={vIndex}>
                <IndexTable.Row
                  id={bulkImport.id}
                  selected={false} // Adjust this according to your selected resources logic
                  position={vIndex}
                >
                  {/* Extract ASINs using regex and map each ASIN and add comma */}
                  <IndexTable.Cell>
                    {bulkImport.asin.match(/[A-Z0-9]{10}/g).map((asin, index, arr) => (
                      <React.Fragment key={index}>
                        {asin}
                        {index < arr.length - 1 && ', '}
                        {(index + 1) % 4 === 0 && <br />}
                      </React.Fragment>
                    ))}
                  </IndexTable.Cell>
                  <IndexTable.Cell>                    
                    {bulkImport.failed_asin}
                  </IndexTable.Cell>
                  <IndexTable.Cell>{bulkImport.status == 0 ? <Badge tone="critical">Import in process</Badge> : <Badge tone="success">Imported</Badge>}</IndexTable.Cell>
                  <IndexTable.Cell>{bulkImport.failed}/{bulkImport.total}</IndexTable.Cell>
                  <IndexTable.Cell>{bulkImport.created_at}</IndexTable.Cell>
                </IndexTable.Row>
              </React.Fragment>
            ))}

            
            

          </IndexTable>
        </Card>
      </div>
    </Page>
  );
}
