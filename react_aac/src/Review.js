import {
	AppProvider,
	Banner,
	Button,
	DataTable,
	FooterHelp,
	Layout,
	LegacyCard,
	Page,
	Card,
	Badge,
	ChoiceList,
	IndexFilter,
	IndexTable,
	useSetIndexFiltersMode,
	useIndexResourceState,
	IndexFilters,
	Pagination,
	Popover,
	ActionList,
	Text,
	Modal,
	TextContainer,
	Frame,
	TextStyle,
} from "@shopify/polaris";
import React, { useCallback, useState, useEffect, useContext } from "react";
import axios from "axios";
import { Link, useNavigate } from "react-router-dom"; // or 'next/link' if using Next.js
import wrap from "word-wrap";
import { ToastContainer, toast } from 'react-toastify';
import 'react-toastify/dist/ReactToastify.css';

function Review() {
	const [data, setData] = useState("");
	const [token, setToken] = useState("");
	const [Products, setProducts] = useState("");
	const [Productlist, setProductlist] = useState("");
	const [reviewData, setReviewData] = useState("");
	const [activeDropdowns, setActiveDropdowns] = useState({});
	const tempcode = localStorage.getItem("tempcode");
	const [active, setActive] = useState(false);
	const [activeDropdowns2, setActiveDropdowns2] = useState({});
	const [sortSelected, setSortSelected] = useState(["product asc"]);

	const navigate = useNavigate();
	const titleCellStyle = {
		wordWrap: "break-word",
		whiteSpace: "normal",
	};

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
				// console.log('response-token', response.data.token);
			} catch (error) {
				console.error("Error fetching token:", error);
			}
		};

		getShopifyThemeId();
	}, []); // Empty dependency array means this useEffect runs only once

	// console.log('token',token);

	useEffect(() => {
		const fetchProducts = async () => {
			if (token) {
				// Check if token is available
				try {
					const response = await axios.get(
						`${process.env.REACT_APP_BASE_URL}/product?lang=en-us`,
						{
							headers: {
								Authorization: `Bearer ${token}`,
							},
						}
					);
					console.log("data responce", response);
					setProducts(response);
					setProductlist(response.data.data);
				} catch (error) {
					console.error("Error fetching products:", error);
				}
			}
		};

		fetchProducts();
	}, [token]); // This useEffect will run whenever 'token' changes

	const viewReviews = async (asin) => {
		try {
			const response = await axios.get(
				`${process.env.REACT_APP_BASE_URL}/review?id=${asin}&lang=en-us`,
				{
					headers: {
						Authorization: `Bearer ${token}`,
					},
				}
			);
			const data = await response.data.data;
			setReviewData(data);
			console.log("Fetch Reviews Data:", data);
			setActive(true);
		} catch (error) {
			console.error("Error fetching fetch reviews:", error);
		}
	};

	const fetchReviews = async (asin) => {
		try {
			const response = await axios.post(
				`${process.env.REACT_APP_BASE_URL}/product/fetchAmzReviews?lang=en-us`,
				{ id: asin },
				{
					headers: {
						Authorization: `Bearer ${token}`, // Use token from response data
					},
				}
			);
			const dataResponce = await response.data[0];
			// console.log("View Reviews Data:", data);
			toast.success(dataResponce);
		} catch (error) {
			console.error("Error fetching view reviews:", error);
		}
	};

	const deleteReviews = async (id) => {
		try {
			const response = await axios.post(
				`${process.env.REACT_APP_BASE_URL}/review/destroy/${id}`,
				{}, // No body content for the POST request
				{
					headers: {
						Authorization: `Bearer ${token}`,
					},
				}
			);
			const data = response.data;
			// setReviewData(data);
			console.log("Fetch Reviews Data:", data.message);
			toast.success(data.message);
			// Set a timeout to refresh the page after 5 seconds
			setTimeout(() => {
				window.location.reload();
			}, 5000);
			setActive(true);
		} catch (error) {
			console.error("Error fetching fetch reviews:", error);
			toast.error("Something went wrong !!");
		}
	};







	const toggleDropdown = (index) => {
		setActiveDropdowns((prevState) => ({
			...prevState,
			[index]: !prevState[index],
		}));
	};

	const toggleDropdown2 = (index) => {
		setActiveDropdowns2((prevState) => ({
			...prevState,
			[index]: !prevState[index],
		}));
	};

	function disambiguateLabel(key, value) {
		switch (key) {
			case "type":
				return value.map((val) => `type: ${val}`).join(", ");
			case "tone":
				return value.map((val) => `tone: ${val}`).join(", ");
			default:
				return value;
		}
	}
	function isEmpty(value) {
		if (Array.isArray(value)) {
			return value.length === 0;
		} else {
			return value === "" || value == null;
		}
	}
	const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));
	const [itemStrings, setItemStrings] = useState([
		// "All",
		// "Active",
		// "Draft",
		// "Archived",
	]);
	const deleteView = (index) => {
		const newItemStrings = [...itemStrings];
		newItemStrings.splice(index, 1);
		setItemStrings(newItemStrings);
		setSelected(0);
	};
	const duplicateView = async (name) => {
		setItemStrings([...itemStrings, name]);
		setSelected(itemStrings.length);
		await sleep(1);
		return true;
	};
	const tabs = itemStrings.map((item, index) => ({
		content: item,
		index,
		onAction: () => { },
		id: `${item}-${index}`,
		isLocked: index === 0,
		actions:
			index === 0
				? []
				: [
					{
						type: "rename",
						onAction: () => { },
						onPrimaryAction: async (value) => {
							const newItemsStrings = tabs.map(
								(item, idx) => {
									if (idx === index) {
										return value;
									}
									return item.content;
								}
							);
							await sleep(1);
							setItemStrings(newItemsStrings);
							return true;
						},
					},
					{
						type: "duplicate",
						onPrimaryAction: async (name) => {
							await sleep(1);
							duplicateView(name);
							return true;
						},
					},
					{
						type: "edit",
					},
					{
						type: "delete",
						onPrimaryAction: async () => {
							await sleep(1);
							deleteView(index);
							return true;
						},
					},
				],
	}));
	const [selected, setSelected] = useState(0);
	const onCreateNewView = async (value) => {
		await sleep(500);
		setItemStrings([...itemStrings, value]);
		setSelected(itemStrings.length);
		return true;
	};
	const sortOptions = [
		{ label: "Product", value: "product asc", directionLabel: "Ascending" },
		{
			label: "Product",
			value: "product desc",
			directionLabel: "Descending",
		},
		{ label: "Status", value: "tone asc", directionLabel: "A-Z" },
		{ label: "Status", value: "tone desc", directionLabel: "Z-A" },
		{ label: "Type", value: "type asc", directionLabel: "A-Z" },
		{ label: "Type", value: "type desc", directionLabel: "Z-A" },
		{ label: "Vendor", value: "vendor asc", directionLabel: "Ascending" },
		{ label: "Vendor", value: "vendor desc", directionLabel: "Descending" },
	];
	const { mode, setMode } = useSetIndexFiltersMode();
	const onHandleCancel = () => { };
	const onHandleSave = async () => {
		await sleep(1);
		return true;
	};
	const primaryAction =
		selected === 0
			? {
				type: "save-as",
				onAction: onCreateNewView,
				disabled: false,
				loading: false,
			}
			: {
				type: "save",
				onAction: onHandleSave,
				disabled: false,
				loading: false,
			};
	const [tone, setStatus] = useState(undefined);
	const [type, setType] = useState(undefined);
	const [queryValue, setQueryValue] = useState("");
	const handleStatusChange = useCallback((value) => setStatus(value), []);
	const handleTypeChange = useCallback((value) => setType(value), []);
	const handleFiltersQueryChange = useCallback((value) => setQueryValue(value),[]);
	const handleStatusRemove = useCallback(() => setStatus(undefined), []);
	const handleTypeRemove = useCallback(() => setType(undefined), []);
	const handleQueryValueRemove = useCallback(() => setQueryValue(""), []);
	const handleFiltersClearAll = useCallback(() => {
		handleStatusRemove();
		handleTypeRemove();
		handleQueryValueRemove();
	}, [handleStatusRemove, handleQueryValueRemove, handleTypeRemove]);
	const filters = [
		{
			key: "tone",
			label: "Status",
			filter: (
				<ChoiceList
					title="tone"
					titleHidden
					choices={[
						{ label: "Active", value: "active" },
						{ label: "Draft", value: "draft" },
						{ label: "Archived", value: "archived" },
					]}
					selected={tone || []}
					onChange={handleStatusChange}
					allowMultiple
				/>
			),
			shortcut: true,
		},
		{
			key: "type",
			label: "Type",
			filter: (
				<ChoiceList
					title="Type"
					titleHidden
					choices={[
						{ label: "Brew Gear", value: "brew-gear" },
						{ label: "Brew Merch", value: "brew-merch" },
					]}
					selected={type || []}
					onChange={handleTypeChange}
					allowMultiple
				/>
			),
			shortcut: true,
		},
	];
	const appliedFilters = [];
	if (tone && !isEmpty(tone)) {
		const key = "tone";
		appliedFilters.push({
			key,
			label: disambiguateLabel(key, tone),
			onRemove: handleStatusRemove,
		});
	}
	if (type && !isEmpty(type)) {
		const key = "type";
		appliedFilters.push({
			key,
			label: disambiguateLabel(key, type),
			onRemove: handleTypeRemove,
		});
	}

	const shproducts = Productlist;
	const resourceName = {
		singular: "review",
		plural: "Reviews",
	};

	// Pagination state
	const itemsPerPage = 10;
	const [currentPage, setCurrentPage] = useState(1);
	const totalPages = Math.ceil(shproducts.length / itemsPerPage);
	const startIndex = (currentPage - 1) * itemsPerPage;
	const endIndex = currentPage * itemsPerPage;

	const paginate = (items, pageNumber, pageSize) => {
		const start = (pageNumber - 1) * pageSize;
		return items.slice(start, start + pageSize);
	};

	const paginatedProducts = paginate(shproducts, currentPage, itemsPerPage);

	const { selectedResources, allResourcesSelected, handleSelectionChange } =
		useIndexResourceState(shproducts);

	const rowMarkup = Array.isArray(paginatedProducts)
		? paginatedProducts.map(({ product_id, title, variants }, index) => {
			const wrappedTitle = wrap(title, { width: 50, indent: "" });
			return (
				<React.Fragment key={product_id}>
					<IndexTable.Row
						id={product_id}
						selected={false} // Adjust this according to your selected resources logic
						position={index}
						showSelection={false}
						selectable={false}
					>
						{variants &&
							Array.isArray(variants) &&
							variants.map((variant, vIndex) => (
								<React.Fragment key={vIndex}>
									<IndexTable.Cell>
										<img
											src={variant.main_image.imgurl}
											width="100"
											height="100"
										/>
									</IndexTable.Cell>
								</React.Fragment>
							))}

						<IndexTable.Cell>
							{wrappedTitle.split("\n").map((line, idx) => (
								<span key={idx}>
									{line}
									<br />
								</span>
							))}
						</IndexTable.Cell>

						{variants &&
							Array.isArray(variants) &&
							variants.map((variant, vIndex) => (
								<React.Fragment key={vIndex}>
									<IndexTable.Cell>
										{variant.asin}
									</IndexTable.Cell>
									<IndexTable.Cell>
										{variant.price}
									</IndexTable.Cell>
									<IndexTable.Cell>
										{variant.reviews.length >= 1 ? (
											<div>
												{variant.reviews.length}{" "}
												reviews
											</div>
										) : (
											<div>No review found</div>
										)}
									</IndexTable.Cell>

									<IndexTable.Cell>
										<Popover
											active={
												activeDropdowns[
												`${index}-${vIndex}`
												] || false
											}
											activator={
												<Button
													onClick={() =>
														toggleDropdown(
															`${index}-${vIndex}`
														)
													}
												>
													Actions
												</Button>
											}
											onClose={() =>
												toggleDropdown(
													`${index}-${vIndex}`
												)
											}
										>
											<ActionList
												items={[
													{
														content:
															"View Reviews",
														onAction: () =>
															viewReviews(
																variant.asin
															),
													},
													{
														content:
															"Fetch Reviews",
														onAction: () =>
															fetchReviews(
																variant.asin
															),
													},
												]}
											/>
										</Popover>
									</IndexTable.Cell>
								</React.Fragment>
							))}
					</IndexTable.Row>
				</React.Fragment>
			);
		})
		: null;

	const handlePreviousPage = () => {
		if (currentPage > 1) {
			setCurrentPage(currentPage - 1);
		}
	};

	const handleNextPage = () => {
		if (currentPage < totalPages) {
			setCurrentPage(currentPage + 1);
		}
	};

	const handleChange = useCallback(() => setActive(!active), [active]);

	const navigateToEditPage = (id) => {
		navigate(`/review-edit/${id}`);
	};

	const unPublishedReviews = async (id) => {
		try {
			const response = await axios.post(
				`${process.env.REACT_APP_BASE_URL}/review/unpublish/${id}`,
				{},
				{
					headers: {
						Authorization: `Bearer ${token}`,
					},
				}
			);
			const data = response.data;
			// setReviewData(data);
			console.log("unpublish review Data:", data.message);
			toast.success(data.message);
			// Set a timeout to refresh the page after 5 seconds
			setTimeout(() => {
				window.location.reload();
			}, 5000);
			setActive(true);
		} catch (error) {
			console.error("Error fetching fetch reviews:", error);
			toast.error("Something went wrong !!");
		}
	};



	const [currentPageReview, setcurrentPageReview] = useState(1);
	const itemsPerPageReview = 10;

	const totalPagesReview = Math.ceil(reviewData.length / itemsPerPageReview);

	const handlePageChange = (newPage) => {
		setcurrentPageReview(newPage);
	};

	// const paginatedData = reviewData.slice((currentPageReview - 1) * itemsPerPageReview, currentPageReview * itemsPerPageReview);
	const paginatedData = Array.isArray(reviewData)
		? reviewData.slice((currentPageReview - 1) * itemsPerPageReview, currentPageReview * itemsPerPageReview)
		: [];

	const startIndexReview = (currentPageReview - 1) * itemsPerPageReview + 1;
	const endIndexReview = Math.min(currentPageReview * itemsPerPageReview, reviewData.length);

	return (
		<Page
			title={"Reviews"}
			primaryAction={
				<Link to="/ImportProductByURL">
					<Button className="Polaris-Button Polaris-Button--primary">
						Add product
					</Button>
				</Link>
			}
			// primaryAction={{ content: "Add product" }}

			secondaryActions={[
				{
					content: (
						<Link
							target="_blank"
							to="https://infoshoreapps.zendesk.com/hc/en-us/sections/360003163653-Manage-Products"
						>
							<Button variant="primary" tone="critical">
								Need Help?
							</Button>
						</Link>
					),
				},
			]}
		>
			<Card padding="0">
				<IndexFilters
					sortOptions={sortOptions}
					sortSelected={sortSelected}
					queryValue={queryValue}
					queryPlaceholder="Searching in all"
					onQueryChange={handleFiltersQueryChange}
					onQueryClear={() => setQueryValue('')}
					onSort={setSortSelected}
					primaryAction={primaryAction}
					cancelAction={{
						onAction: onHandleCancel,
						disabled: false,
						loading: false,
					}}
					tabs={tabs}
					selected={selected}
					onSelect={setSelected}
					canCreateNewView
					onCreateNewView={onCreateNewView}
					filters={filters}
					appliedFilters={appliedFilters}
					onClearAll={handleFiltersClearAll}
					mode={mode}
					setMode={setMode}
				/>
				<IndexTable
					resourceName={resourceName}
					showSelection={false}
					selectable={false}
					itemCount={shproducts.length}
					onSelectionChange={handleSelectionChange}
					sortable={[false, true, true, true, true, true, true]}
					headings={[
						{ title: "Image" },
						{ title: "Title" },
						{ title: "ASIN" },
						{ title: "Price" },
						{ title: "Status" },
						{ title: "Actions" },
					]}
				>
					{rowMarkup}
				</IndexTable>

				<div
					style={{
						display: "flex",
						justifyContent: "space-between",
						alignItems: "center",
						padding: "16px",
					}}
				>
					<Pagination
						hasPrevious={currentPage > 1}
						onPrevious={handlePreviousPage}
						hasNext={currentPage < totalPages}
						onNext={handleNextPage}
					/>
					<div>
						Showing {startIndex} to {endIndex} of{" "}
						{shproducts.length} entries
					</div>
				</div>
			</Card>
			<ToastContainer />

			<div style={{ height: '500px' }}>
				<Frame>
					<Modal
						size="large"
						open={active}
						onClose={handleChange}
						title="View Total Review"
					>
						<Modal.Section>
							<TextContainer>
								<IndexTable
									resourceName={{ singular: 'review', plural: 'reviews' }}
									itemCount={reviewData.length}
									showSelection={false}
									selectable={false}
									sortable={[false, false, false, false, false]}
									headings={[
										{ title: "Review Title" },
										{ title: "Review Date" },
										{ title: "Rating" },
										{ title: "Status" },
										{ title: "Action" },
									]}
								>
									{paginatedData.map((data, rIndex) => (
										<IndexTable.Row
											id={data.id}
											key={rIndex}
											selected={false} // Adjust this according to your selected resources logic
										>
											<IndexTable.Cell>{data.reviewTitle}</IndexTable.Cell>
											<IndexTable.Cell>{data.reviewDate}</IndexTable.Cell>
											<IndexTable.Cell>{data.rating}</IndexTable.Cell>
											<IndexTable.Cell>{data.status}</IndexTable.Cell>
											<IndexTable.Cell>
												<Popover
													active={activeDropdowns2[`${rIndex}-${rIndex}`] || false}
													activator={
														<Button
															onClick={() =>
																toggleDropdown2(`${rIndex}-${rIndex}`)
															}
														>
															Actions
														</Button>
													}
													onClose={() =>
														toggleDropdown2(`${rIndex}-${rIndex}`)
													}
												>
													<ActionList
														items={[
															{
																content: <a href={data.variants.detail_page_url} target="_blank" rel="noopener noreferrer">View on Amazon</a>,
															},
															{
																content: "Unpublish",
																onAction: () => unPublishedReviews(data.id),
															},
															{
																content: "Edit",
																onAction: () => navigateToEditPage(data.id),
															},
															{
																content: "Delete",
																onAction: () => deleteReviews(data.id),
															},
														]}
													/>
												</Popover>
											</IndexTable.Cell>
										</IndexTable.Row>
									))}
								</IndexTable>
								<div
									style={{
										display: "flex",
										justifyContent: "space-between",
										alignItems: "center",
										padding: "16px",
									}}
								>
									<Pagination
										hasPrevious={currentPageReview > 1}
										onPrevious={() => handlePageChange(currentPageReview - 1)}
										hasNext={currentPageReview < totalPagesReview}
										onNext={() => handlePageChange(currentPageReview + 1)}
									/>
									<div>
										Showing {startIndexReview} to {endIndexReview} of {reviewData.length} entries
									</div>
								</div>

							</TextContainer>
						</Modal.Section>
					</Modal>
				</Frame>
			</div>
		</Page>
	);
}

export default Review;