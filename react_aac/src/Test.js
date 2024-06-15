import React, { useEffect, useState } from 'react';
import axios from 'axios';

const Test = () => {
  const [textValue, setTextValue] = useState('');
  const [responseData, setResponseData] = useState(null); // State to store response data
  const [responseData2, setResponseData2] = useState(null); // State to store response data
  const [loading, setLoading] = useState(false); // State to track loading state
  const [responseTime, setResponseTime] = useState(null); // State to store response time
  const [token, setToken] = useState(null); // State to store response time

  const tempcode = localStorage.getItem('tempcode'); 
  const initialValues = {
    key: tempcode,
  };
  
  useEffect(() => {
    const getShopifyThemeId = async () => {
      try {
        const response = await axios.post(`${process.env.REACT_APP_BASE_URL}/authenticate`, initialValues);
        setToken(response.data.token); // Set response data in state
      } catch (error) {
        console.error('Error fetching token:', error);
      }
    };

    getShopifyThemeId();
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true); // Start loading
    
    try {
      const startTime = Date.now(); // Record start time
      const response = await axios.post(`${process.env.REACT_APP_BASE_URL}/submit-form?lang=en-us`,
          { 
              text_value: textValue 
          },
          {
              headers: {
                  Authorization: `Bearer ${token}` // Use token from response data
              }
          }
      );
      const endTime = Date.now(); // Record end time
      const responseTimeInSeconds = (endTime - startTime) / 1000; // Calculate response time in seconds
      setResponseTime(responseTimeInSeconds); // Set response time
      setResponseData2(response.data.response); // Set response data in state
    } catch (error) {
      console.error(error);
    } finally {
      setLoading(false); // Stop loading
    }
  };

  return (
    <div>
      <form onSubmit={handleSubmit}>
        <input 
          type="text" 
          value={textValue} 
          onChange={(e) => setTextValue(e.target.value)} 
          placeholder="Enter text" 
        />
        <button type="submit" disabled={loading}>Submit</button>
      </form>

      {/* Display response data in a table */}
      {/* Display response name */}
      {responseData2 && (
        <div>
          <h3>Product Name:</h3>
          <p><img src={responseData2.mainImage}/></p>
          <p>{responseData2.name}</p>
          <p>{responseData2.price}</p>
          <p>{responseData2.parentAsin}</p>          
          <p>cancel</p>          
          <p>add product button</p>          
        </div>
      )}
      

      {/* Display response data */}
      {responseData2 && (
        <div>
          <h3>Response Data:</h3>
          <pre>{JSON.stringify(responseData2, null, 2)}</pre>
          {responseTime && (
            <p>Response received in {responseTime.toFixed(2)} seconds.</p>
          )}
        </div>
      )}
    </div>
  );
};

export default Test;
