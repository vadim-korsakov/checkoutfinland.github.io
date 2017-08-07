require 'spec_helper'

describe 'index', :type => :feature do
  before do
    visit '/'
  end

  it 'has the correct title header' do
    expect(page).to have_selector 'h1'
    within 'h1#introduction' do
      expect(page).to have_content /Introduction/i
    end
  end

  it 'has a paragraph in there too' do
    expect(page).to have_selector 'p'
  end

  it ‘ensure test code is available’ do
    expect(page).to have_content /The whole test code is available at: [https:\/\/github.com\/CheckoutFinland\/checkoutfinland.github.io\/tree\/master\/examples\/payment-wall]\/
  end
end
