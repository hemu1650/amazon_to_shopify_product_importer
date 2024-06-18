import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { useParams } from 'react-router-dom';
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';
import {
	Form,
	FormLayout,
	TextField,
	Button,
	Page,
	Card,
	Select,
} from '@shopify/polaris';

export default function ReviewEdit() {
	const [isLoading, setIsLoading] = useState(false);
	const [data, setData] = useState('');
	const [token, setToken] = useState('');
	const tempcode = localStorage.getItem('tempcode');

	const initialValues = {
		key: tempcode,
	};
	const [reviewData, setReviewData] = useState(null); // Initialize as null or an empty object depending on your API response
	const [editedData, setEditedData] = useState({
		verified: '',
		rating: '',
		authorName: '',
		reviewDate: '',
		reviewTitle: '',
		reviewDetails: '',
	});

	const { id } = useParams();

	useEffect(() => {
		const getShopifyThemeId = async () => {
			try {
				const response = await axios.post(`${process.env.REACT_APP_BASE_URL}/authenticate`, initialValues);
				setData(response.data);
				setToken(response.data.token);
			} catch (error) {
				console.error('Error fetching token:', error);
			}
		};

		getShopifyThemeId();
	}, []);

	useEffect(() => {
		const fetchBulkImports = async () => {
			if (token) { // Check if token is available
				try {

					const response = await axios.get(`${process.env.REACT_APP_BASE_URL}/review/${id}`, {
						headers: {
							'Authorization': `Bearer ${token}`,
						},
					});
					setReviewData(response.data);
					setEditedData({
						verified: response.data.verifiedFlag,
						rating: response.data.rating,
						authorName: response.data.authorName,
						reviewDate: response.data.reviewDate,
						reviewTitle: response.data.reviewTitle,
						reviewDetails: response.data.reviewDetails.replace(/<[^>]+>/g, ''),
					});
				} catch (error) {
					console.error('Error fetching products:', error);
				}
			}
		};

		fetchBulkImports();
	}, [token]); // This useEffect will run whenever 'token' changes

	const handleSubmit = async () => {
		try {
			setIsLoading(true);
			const response = await axios.post(
				`${process.env.REACT_APP_BASE_URL}/review/update/${id}`,
				editedData,
				{
					headers: {
						Authorization: `Bearer ${token}`,
					},
				}
			);
			setIsLoading(false);
			toast.success('Review updated successfully');
		} catch (error) {
			setIsLoading(false);
			console.error('Error updating review:', error);
			toast.error('Failed to update review');
		}
	};

	return (
		<div className='edit-review'>
			<Page title={'Edit Review'}>
				<ToastContainer />
				<Card>
					<Form noValidate onSubmit={handleSubmit}>
						<FormLayout>
							<TextField
								disabled
								label='Verified'
								value={editedData.verified}
								onChange={(value) =>
									setEditedData((prevState) => ({
										...prevState,
										verified: value,
									}))
								}
							/>
							<TextField
								label='Rating'
								type='text'
								autoComplete='off'
								value={editedData.rating}
								onChange={(value) =>
									setEditedData((prevState) => ({
										...prevState,
										rating: value,
									}))
								}
							/>
							<TextField
								disabled
								label='Author Name'
								type='text'
								autoComplete='off'
								value={editedData.authorName}
								onChange={(value) =>
									setEditedData((prevState) => ({
										...prevState,
										authorName: value,
									}))
								}
							/>
							<TextField
								disabled
								label='Review date'
								type='text'
								autoComplete='off'
								value={editedData.reviewDate}
								onChange={(value) =>
									setEditedData((prevState) => ({
										...prevState,
										reviewDate: value,
									}))
								}
							/>
							<TextField
								disabled
								label='Review Title'
								type='text'
								autoComplete='off'
								value={editedData.reviewTitle}
								onChange={(value) =>
									setEditedData((prevState) => ({
										...prevState,
										reviewTitle: value,
									}))
								}
							/>
							<label>Review Detail</label>
							<textarea
								label='Review Detail'
								type='textarea'
								autoComplete='off'
								className='review-description'
								value={editedData.reviewDetails}
								onChange={(event) =>
									setEditedData((prevState) => ({
										...prevState,
										reviewDetails: event.target.value,
									}))
								}
							/>
							<div style={{ display: 'flex', gap: '8px' }}>
								<Button submit loading={isLoading} primary>
									Submit
								</Button>
							</div>
						</FormLayout>
					</Form>
				</Card>
			</Page>
		</div>
	);
}
